<?php

declare(strict_types=1);

namespace App\Integration\Numelib\Security;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;

/**
 * Authentifie seulement le serveur du Portail Numelib, PAS l'utilisateur final.
 * Chaque utilisateur est résolu via une association permanente Numelib-Symfony.
 */
final class PortalBridgeClientVerifier
{
    public function __construct(
        #[Autowire('%env(PORTAL_BRIDGE_SHARED_SECRET)%')]
        private readonly string $sharedSecret,
        #[Autowire('%env(PORTAL_BRIDGE_WRITE_SECRET)%')]
        private readonly string $writeSecret,
    ) {
    }

    public function isTrusted(Request $request, bool $write = false): bool
    {
        // Les écritures exigent une clé DISTINCTE de celle des lectures.
        // Absente, trop courte ou égale à la clé de lecture : refus systématique.
        $secret = $write ? $this->writeSecret : $this->sharedSecret;
        if (strlen($secret) < 43 || ($write && hash_equals($this->sharedSecret, $this->writeSecret))) {
            return false;
        }

        $authorization = $request->headers->get('Authorization', '');
        if (!str_starts_with($authorization, 'Bearer ')) {
            return false;
        }

        return hash_equals($secret, substr($authorization, 7));
    }
}
