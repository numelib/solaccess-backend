<?php

declare(strict_types=1);

namespace App\Integration\Numelib\Service;

use App\Integration\Numelib\Entity\SolaccessPortalAccountLink;
use App\Entity\User;
use App\Integration\Numelib\Exception\PortalAccountLinkException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Seule autorité métier pour les associations Numelib <-> comptes Solaccess.
 * Ne crée aucun User, n'attribue aucun ESP32, et n'infère aucune liaison d'un email.
 * Consommée à la fois par la commande CLI et le Bridge administratif optionnel.
 */
final class PortalAccountLinkManager
{
    private const CLIENT_ID = 'numelib';

    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public static function isValidExternalId(string $id): bool
    {
        return preg_match('/\Arec[A-Za-z0-9_-]{4,61}\z/D', $id) === 1;
    }

    /** @return array<string, mixed> */
    public function statusFor(string $externalId): array
    {
        $link = $this->findLink($externalId);
        return $link === null ? ['linked' => false] : $this->project($link);
    }

    /**
     * Consultation restreinte d'un compte métier pour confirmer manuellement son ID avant de lier.
     * @return array<string, mixed>
     */
    public function candidateFor(int $userId): array
    {
        $user = $this->findUser($userId);
        if ($user === null) {
            throw new PortalAccountLinkException('user_not_found', 404);
        }

        $alreadyLinked = $this->em->getRepository(SolaccessPortalAccountLink::class)->findOneBy([
            'clientId' => self::CLIENT_ID,
            'solaccessUser' => $user,
        ]) !== null;

        return [
            'solaccessUserId' => $user->getId(),
            'email' => $user->getEmail(),
            'firstname' => $user->getFirstname(),
            'lastname' => $user->getLastname(),
            'accountActive' => $user->isActive() === true,
            'eligible' => $this->isBusinessUser($user) && $user->isActive() === true && !$alreadyLinked,
            'alreadyLinked' => $alreadyLinked,
        ];
    }

    /** @return array<string, mixed> */
    public function link(string $externalId, int $userId): array
    {
        $this->validateExternalId($externalId);
        if ($userId <= 0) {
            throw new PortalAccountLinkException('invalid_user_id', 422);
        }

        try {
            /** @var SolaccessPortalAccountLink $link */
            $link = $this->em->wrapInTransaction(function (EntityManagerInterface $em) use ($externalId, $userId): SolaccessPortalAccountLink {
                if ($this->findLink($externalId) !== null) {
                    throw new PortalAccountLinkException('already_linked', 409);
                }

                $user = $this->findUser($userId);
                if ($user === null) {
                    throw new PortalAccountLinkException('user_not_found', 404);
                }
                $this->requireEligibleUser($user);

                $existing = $em->getRepository(SolaccessPortalAccountLink::class)->findOneBy([
                    'clientId' => self::CLIENT_ID,
                    'solaccessUser' => $user,
                ]);
                if ($existing !== null) {
                    throw new PortalAccountLinkException('user_already_linked', 409);
                }

                $link = new SolaccessPortalAccountLink(self::CLIENT_ID, $externalId, $user);
                // Contrairement au défaut historique de l'entité, une NOUVELLE liaison
                // est systématiquement désactivée jusqu'à confirmation explicite.
                $link->setEnabled(false);
                $link->setCanManageCredentials(false);
                $em->persist($link);
                return $link;
            });
        } catch (UniqueConstraintViolationException) {
            // La contrainte SQL est la dernière barrière en cas de requêtes concurrentes.
            throw new PortalAccountLinkException('association_conflict', 409);
        }

        return $this->project($link);
    }

    /** @return array<string, mixed> */
    public function setEnabled(string $externalId, bool $enabled): array
    {
        $link = $this->requireLink($externalId);
        if ($enabled) {
            $this->requireEligibleUser($link->getSolaccessUser());
        } else {
            // Évite qu'une permission d'écriture revienne implicitement à la réactivation.
            $link->setCanManageCredentials(false);
        }
        $link->setEnabled($enabled);
        $this->em->flush();
        return $this->project($link);
    }

    /**
     * Réglage conservé pour l'administration technique CLI, mais NON exposé
     * à l'intranet Numelib via ce lot.
     * @return array<string, mixed>
     */
    public function setCredentialManagement(string $externalId, bool $allowed): array
    {
        $link = $this->requireLink($externalId);
        if ($allowed) {
            $this->requireEligibleUser($link->getSolaccessUser());
            if (!$link->isEnabled()) {
                throw new PortalAccountLinkException('portal_disabled', 409);
            }
        }
        $link->setCanManageCredentials($allowed);
        $this->em->flush();
        return $this->project($link);
    }

    public function unlink(string $externalId): void
    {
        $link = $this->requireLink($externalId);
        // Dissociation volontaire en deux temps afin d'éviter une coupure par erreur.
        if ($link->isEnabled()) {
            throw new PortalAccountLinkException('disable_before_unlink', 409);
        }
        $this->em->remove($link);
        $this->em->flush();
    }

    private function findLink(string $externalId): ?SolaccessPortalAccountLink
    {
        $this->validateExternalId($externalId);
        /** @var SolaccessPortalAccountLink|null $link */
        $link = $this->em->getRepository(SolaccessPortalAccountLink::class)->findOneBy([
            'clientId' => self::CLIENT_ID,
            'externalUserId' => $externalId,
        ]);
        return $link;
    }

    private function requireLink(string $externalId): SolaccessPortalAccountLink
    {
        return $this->findLink($externalId)
            ?? throw new PortalAccountLinkException('not_linked', 404);
    }

    private function findUser(int $id): ?User
    {
        if ($id <= 0) {
            return null;
        }
        /** @var User|null $user */
        $user = $this->em->getRepository(User::class)->find($id);
        return $user;
    }

    private function validateExternalId(string $externalId): void
    {
        if (!self::isValidExternalId($externalId)) {
            throw new PortalAccountLinkException('invalid_external_id', 422);
        }
    }

    private function isBusinessUser(User $user): bool
    {
        // getRoles() ajoute toujours ROLE_USER, même aux administrateurs.
        return array_diff($user->getRoles(), ['ROLE_USER']) === [];
    }

    private function requireEligibleUser(User $user): void
    {
        if ($user->isActive() !== true || !$this->isBusinessUser($user)) {
            throw new PortalAccountLinkException('user_not_eligible', 409);
        }
    }

    /** @return array<string, mixed> */
    private function project(SolaccessPortalAccountLink $link): array
    {
        $user = $link->getSolaccessUser();
        return [
            'linked' => true,
            'solaccessUserId' => $user->getId(),
            'email' => $user->getEmail(),
            'firstname' => $user->getFirstname(),
            'lastname' => $user->getLastname(),
            'accountActive' => $user->isActive() === true,
            'enabled' => $link->isEnabled(),
            'canManageCredentials' => $link->canManageCredentials(),
        ];
    }
}
