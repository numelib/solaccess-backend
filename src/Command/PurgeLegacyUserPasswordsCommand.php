<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\LockMode;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Nettoyage ponctuel des anciens hashes des comptes metier.
 * Par defaut, aucune ecriture. Les comptes administrateurs et les roles
 * inconnus sont exclus. Les lignes sont verrouillees et revérifiees avant
 * chaque effacement dans une transaction unique.
 */
#[AsCommand(
    name: 'app:users:purge-legacy-passwords',
    description: 'Audit puis purge des anciens mots de passe des comptes metier non administrateurs.',
)]
final class PurgeLegacyUserPasswordsCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'execute',
            null,
            InputOption::VALUE_NONE,
            'Applique la purge ; sans cette option, la commande ne modifie rien.',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Hydratation de champs uniquement : ne charge pas les hashes en memoire.
        $rows = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->select('u.id', 'u.roles')
            ->where('u.password IS NOT NULL')
            ->getQuery()
            ->getArrayResult();

        $candidateIds = [];
        $administrators = 0;
        $otherRoles = 0;

        foreach ($rows as $row) {
            $roles = $row['roles'] ?? null;
            if (!is_array($roles) || count(array_filter($roles, 'is_string')) !== count($roles)) {
                $io->error('Roles inattendus pour un compte : aucune modification effectuee.');
                return Command::FAILURE;
            }

            if (in_array('ROLE_ADMIN', $roles, true)) {
                ++$administrators;
            } elseif (array_diff($roles, ['ROLE_USER']) !== []) {
                // Ne jamais reinitialiser le mot de passe d'un role non prevu.
                ++$otherRoles;
            } else {
                $candidateIds[] = (int) $row['id'];
            }
        }

        $io->definitionList(
            ['Comptes metier avec ancien hash' => count($candidateIds)],
            ['Comptes administrateurs preserves' => $administrators],
            ['Autres roles preserves (verification manuelle)' => $otherRoles],
        );

        if (!$input->getOption('execute')) {
            $io->note('Audit uniquement. Ajouter --execute pour supprimer les hashes des comptes metier.');
            return Command::SUCCESS;
        }

        if ($candidateIds === []) {
            $io->success('Aucun ancien hash de compte metier a supprimer.');
            return Command::SUCCESS;
        }

        try {
            $modified = $this->entityManager->wrapInTransaction(
                function (EntityManagerInterface $em) use ($candidateIds): int {
                    $modified = 0;
                    foreach ($candidateIds as $id) {
                        // Verrouillages pour eviter une promotion de role concurrente.
                        $user = $em->find(User::class, $id, LockMode::PESSIMISTIC_WRITE);

                        if (!$user instanceof User || !$this->isBusinessUser($user)) {
                            throw new \RuntimeException(sprintf(
                                'Le compte %d a change de role : transaction annulee.',
                                $id,
                            ));
                        }

                        if ($user->getPassword() !== null) {
                            $user->setPassword(null);
                            ++$modified;
                        }
                    }

                    return $modified;
                },
            );
        } catch (\Throwable $exception) {
            $io->error('Purge interrompue et transaction annulee : ' . $exception->getMessage());
            return Command::FAILURE;
        }

        $io->success(sprintf('%d hash(s) de compte metier supprime(s). Administrateurs inchanges.', $modified));
        return Command::SUCCESS;
    }

    private function isBusinessUser(User $user): bool
    {
        // getRoles() inclut toujours ROLE_USER, meme pour les admins.
        return array_diff($user->getRoles(), ['ROLE_USER']) === [];
    }
}
