<?php

declare(strict_types=1);

namespace App\Integration\Numelib\Security;

use App\Integration\Numelib\Exception\PortalBridgeAccessException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;

/**
 * Optionnel et désactivé sans configuration. Authentifie le serveur Numelib,
 * PAS l'administrateur OTP : son rôle doit être revérifié côté serveur Teable.
 */
final class PortalBridgeAdminVerifier
{
    public function __construct(
        #[Autowire('%env(PORTAL_BRIDGE_ADMIN_SECRET)%')]
        private readonly string $adminSecret,
        #[Autowire('%env(PORTAL_BRIDGE_ADMIN_USER_IDS)%')]
        private readonly string $allowedAdminIds,
        #[Autowire('%env(PORTAL_BRIDGE_SHARED_SECRET)%')]
        private readonly string $readSecret,
        #[Autowire('%env(PORTAL_BRIDGE_WRITE_SECRET)%')]
        private readonly string $writeSecret,
    ) {
    }

    /** @throws PortalBridgeAccessException */
    public function verify(Request $request): string
    {
        if (strlen($this->adminSecret) < 43
            || hash_equals($this->adminSecret, $this->readSecret)
            || hash_equals($this->adminSecret, $this->writeSecret)
            || trim($this->allowedAdminIds) === ''
        ) {
            throw new PortalBridgeAccessException('admin_bridge_unavailable', 401);
        }

        $header = $request->headers->get('Authorization', '');
        if (!is_string($header)
            || !str_starts_with($header, 'Bearer ')
            || !hash_equals($this->adminSecret, substr($header, 7))
        ) {
            throw new PortalBridgeAccessException('unauthorized_admin_bridge', 401);
        }

        $adminId = $request->headers->get('X-Numelib-Admin-User-Id', '');
        if (!is_string($adminId)
            || preg_match('/\Arec[A-Za-z0-9_-]{4,61}\z/D', $adminId) !== 1
            || !in_array($adminId, array_map('trim', explode(',', $this->allowedAdminIds)), true)
        ) {
            throw new PortalBridgeAccessException('admin_not_allowed', 403);
        }

        return $adminId;
    }
}
