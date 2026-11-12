<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\SolaccessPortalAccountLink;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Administration locale uniquement : aucun endpoint public pour lier des comptes.
 */
#[AsCommand(
    name: 'app:solaccess:portal-link',
    description: 'Associer, inspecter, activer, désactiver ou retirer une liaison Numelib / Solaccess',
)]
final class SolaccessPortalLinkCommand extends Command
{
    private const CLIENT_ID = 'numelib';

    public function __construct(private readonly EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('action', InputArgument::REQUIRED, 'link | show | enable | disable | unlink')
            ->addArgument('numelib-user-id', InputArgument::REQUIRED, 'ID de record Utilisateurs (rec...)')
            ->addArgument('solaccess-user-id', InputArgument::OPTIONAL, 'ID numérique de User Symfony (requis pour link)')
            ->setHelp('La liaison est créée explicitement par un administrateur. Aucun rapprochement automatique par e-mail.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $action = (string) $input->getArgument('action');
        $externalId = (string) $input->getArgument('numelib-user-id');
        $userIdArg = $input->getArgument('solaccess-user-id');

        if (!in_array($action, ['link', 'show', 'enable', 'disable', 'unlink'], true)) {
            $io->error('Action inconnue. Choisir link, show, enable, disable ou unlink.');
            return Command::INVALID;
        }
        if (preg_match('/\Arec[A-Za-z0-9_-]{4,61}\z/D', $externalId) !== 1) {
            $io->error('Identifiant Numelib invalide : fournir un ID Teable rec...');
            return Command::INVALID;
        }

        $repo = $this->em->getRepository(SolaccessPortalAccountLink::class);
        /** @var SolaccessPortalAccountLink|null $link */
        $link = $repo->findOneBy(['clientId' => self::CLIENT_ID, 'externalUserId' => $externalId]);

        if ($action === 'link') {
            if (!is_string($userIdArg) || !ctype_digit($userIdArg) || (int) $userIdArg <= 0) {
                $io->error('Pour link, fournir un identifiant numérique User Symfony positif.');
                return Command::INVALID;
            }
            /** @var User|null $user */
            $user = $this->em->getRepository(User::class)->find((int) $userIdArg);
            if ($user === null || $user->isActive() !== true) {
                $io->error('Compte Symfony inconnu ou inactif.');
                return Command::FAILURE;
            }
            if ($link !== null) {
                if ($link->getSolaccessUser()->getId() === $user->getId()) {
                    $io->note('Association existante, aucune modification (utiliser enable si nécessaire).');
                    return Command::SUCCESS;
                }
                $io->error('Cet utilisateur Numelib est déjà lié à un autre compte Symfony : retirer explicitement cette liaison avant de la recréer.');
                return Command::FAILURE;
            }
            $otherLink = $repo->findOneBy(['clientId' => self::CLIENT_ID, 'solaccessUser' => $user]);
            if ($otherLink !== null) {
                $io->error('Ce compte Symfony est déjà lié à un autre utilisateur Numelib.');
                return Command::FAILURE;
            }
            $link = new SolaccessPortalAccountLink(self::CLIENT_ID, $externalId, $user);
            $this->em->persist($link);
            $this->em->flush();
            $io->success(sprintf('Liaison créée : %s -> User Symfony #%d.', $externalId, $user->getId()));
            return Command::SUCCESS;
        }

        if ($link === null) {
            $io->error('Aucune liaison existante pour cet utilisateur Numelib.');
            return Command::FAILURE;
        }
        if ($action === 'show') {
            $io->definitionList(
                ['Client' => $link->getClientId()],
                ['Utilisateur Numelib' => $link->getExternalUserId()],
                ['User Symfony' => (string) $link->getSolaccessUser()->getId()],
                ['État' => $link->isEnabled() ? 'activé' : 'désactivé'],
            );
            return Command::SUCCESS;
        }
        if ($action === 'unlink') {
            $this->em->remove($link);
            $this->em->flush();
            $io->success('Association supprimée.');
            return Command::SUCCESS;
        }
        if ($action === 'enable' && $link->getSolaccessUser()->isActive() !== true) {
            $io->error('Impossible de réactiver une liaison vers un compte Symfony inactif.');
            return Command::FAILURE;
        }
        $link->setEnabled($action === 'enable');
        $this->em->flush();
        $io->success($action === 'enable' ? 'Association activée.' : 'Association désactivée.');
        return Command::SUCCESS;
    }
}
