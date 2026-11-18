<?php

declare(strict_types=1);

namespace App\Integration\Numelib\Command;

use App\Integration\Numelib\Exception\PortalAccountLinkException;
use App\Integration\Numelib\Service\PortalAccountLinkManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/** La CLI et le Bridge utilisent désormais exactement les mêmes règles de liaison. */
#[AsCommand(
    name: 'app:solaccess:portal-link',
    description: 'Associer, inspecter, activer, désactiver ou retirer une liaison Numelib / Solaccess',
)]
final class SolaccessPortalLinkCommand extends Command
{
    public function __construct(private readonly PortalAccountLinkManager $links)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('action', InputArgument::REQUIRED, 'link | show | enable | disable | unlink | credentials-enable | credentials-disable')
            ->addArgument('numelib-user-id', InputArgument::REQUIRED, 'ID de record Utilisateurs (rec...)')
            ->addArgument('solaccess-user-id', InputArgument::OPTIONAL, 'ID numérique de User Symfony (requis pour link)')
            ->setHelp('Les comptes métier sont créés depuis /admin ; aucune recherche automatique par email. Pour unlink, désactiver la liaison au préalable.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $action = (string) $input->getArgument('action');
        $externalId = (string) $input->getArgument('numelib-user-id');
        $userIdArg = $input->getArgument('solaccess-user-id');

        if (!in_array($action, ['link', 'show', 'enable', 'disable', 'unlink', 'credentials-enable', 'credentials-disable'], true)
            || !PortalAccountLinkManager::isValidExternalId($externalId)
        ) {
            $io->error('Action ou identifiant Numelib invalide.');
            return Command::INVALID;
        }

        try {
            if ($action === 'show') {
                $state = $this->links->statusFor($externalId);
                if (!$state['linked']) {
                    $io->error('Aucune liaison existante pour cet utilisateur Numelib.');
                    return Command::FAILURE;
                }
                $io->definitionList(
                    ['Client' => 'numelib'],
                    ['Utilisateur Numelib' => $externalId],
                    ['User Symfony' => (string) $state['solaccessUserId']],
                    ['État' => $state['enabled'] ? 'activé' : 'désactivé'],
                    ['Gestion des badges et codes' => $state['canManageCredentials'] ? 'autorisée' : 'interdite'],
                );
                return Command::SUCCESS;
            }

            if ($action === 'link') {
                if (!is_string($userIdArg) || !ctype_digit($userIdArg) || (int) $userIdArg <= 0) {
                    $io->error('Pour link, fournir un identifiant numérique User Symfony positif.');
                    return Command::INVALID;
                }
                $existing = $this->links->statusFor($externalId);
                if ($existing['linked'] && $existing['solaccessUserId'] === (int) $userIdArg) {
                    $io->note('Association déjà présente, aucune modification.');
                    return Command::SUCCESS;
                }
                $state = $this->links->link($externalId, (int) $userIdArg);
                $io->success(sprintf('Association créée, désactivée par défaut : %s -> User #%d.', $externalId, $state['solaccessUserId']));
                return Command::SUCCESS;
            }

            if ($action === 'unlink') {
                $this->links->unlink($externalId);
                $io->success('Association supprimée. Le compte Symfony et ses équipements sont conservés.');
                return Command::SUCCESS;
            }

            if ($action === 'enable' || $action === 'disable') {
                $this->links->setEnabled($externalId, $action === 'enable');
                $io->success($action === 'enable' ? 'Association activée.' : 'Association désactivée ; permission credentials révoquée.');
                return Command::SUCCESS;
            }

            $this->links->setCredentialManagement($externalId, $action === 'credentials-enable');
            $io->success($action === 'credentials-enable' ? 'Gestion badges et codes autorisée.' : 'Gestion badges et codes désactivée.');
            return Command::SUCCESS;
        } catch (PortalAccountLinkException $e) {
            $io->error(match ($e->getMessage()) {
                'already_linked' => 'Cet identifiant Numelib est déjà associé à un autre compte.',
                'user_already_linked' => 'Ce compte Solaccess est déjà associé à un autre membre Numelib.',
                'disable_before_unlink' => 'Désactiver la liaison avant de la supprimer (disable).',
                'user_not_eligible' => 'Compte Symfony inactif ou non éligible (administrateur / rôle spécial).',
                'portal_disabled' => 'Activer la liaison avant d’autoriser les credentials.',
                'not_linked' => 'Aucune liaison existante.',
                'user_not_found' => 'Compte Symfony introuvable.',
                default => 'Opération refusée : ' . $e->getMessage(),
            });
            return Command::FAILURE;
        }
    }
}
