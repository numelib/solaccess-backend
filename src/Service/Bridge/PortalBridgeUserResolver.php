<?php

declare(strict_types=1);

namespace App\Service\Bridge;

use App\Entity\SolaccessPortalAccountLink;
use App\Entity\User;
use App\Exception\PortalBridgeAccessException;
use App\Security\PortalBridgeClientVerifier;
use App\Security\UserChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccountStatusException;

/**
 * Une seule résolution des droits pour toutes les routes de la passerelle.
 * L'ID Numelib doit avoir été dérivé d'une session OTP serveur par l'appelant.
 */
final class PortalBridgeUserResolver
{
    private const CLIENT_ID = 'numelib';

    public function __construct(
        private readonly PortalBridgeClientVerifier $clients,
        private readonly EntityManagerInterface $em,
        private readonly UserChecker $userChecker,
    ) {
    }

    public function resolve(Request $request, bool $write = false, bool $credentialWrite = false): User
    {
        if (!$this->clients->isTrusted($request, $write || $credentialWrite)) {
            throw new PortalBridgeAccessException('unauthorized_bridge', 401);
        }

        $externalUserId = $request->headers->get('X-Numelib-User-Id', '');
        if (!is_string($externalUserId)
            || preg_match('/\Arec[A-Za-z0-9_-]{4,61}\z/D', $externalUserId) !== 1
        ) {
            throw new PortalBridgeAccessException('invalid_identity', 400);
        }

        /** @var SolaccessPortalAccountLink|null $link */
        $link = $this->em->getRepository(SolaccessPortalAccountLink::class)->findOneBy([
            'clientId' => self::CLIENT_ID,
            'externalUserId' => $externalUserId,
        ]);
        if ($link === null) {
            throw new PortalBridgeAccessException('solaccess_not_linked', 404);
        }
        if (!$link->isEnabled()) {
            throw new PortalBridgeAccessException('solaccess_disabled', 403);
        }
        if ($credentialWrite && !$link->canManageCredentials()) {
            throw new PortalBridgeAccessException('credential_management_disabled', 403);
        }

        $user = $link->getSolaccessUser();
        if ($user->isActive() !== true) {
            throw new PortalBridgeAccessException('solaccess_disabled', 403);
        }
        try {
            $this->userChecker->checkPreAuth($user);
            $this->userChecker->checkPostAuth($user);
        } catch (AccountStatusException) {
            throw new PortalBridgeAccessException('solaccess_disabled', 403);
        }

        return $user;
    }
}
