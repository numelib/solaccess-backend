<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;

/**
 * Authentifie seulement le serveur du Portail Numelib, PAS l'utilisateur final.
 * La connexion utilisateur est vérifiée séparément à chaque ouverture de session.
 */
final class PortalBridgeClientVerifier
{
    public function __construct(
        #[Autowire('%env(PORTAL_BRIDGE_SHARED_SECRET)%')]
        private readonly string $sharedSecret,
    ) {
    }

    public function isTrusted(Request $request): bool
    {
        // En cas de configuration manquante/faible, ne jamais ouvrir la passerelle.
        if (strlen($this->sharedSecret) < 43) {
            return false;
        }

        $authorization = $request->headers->get('Authorization', '');
        if (!str_starts_with($authorization, 'Bearer ')) {
            return false;
        }

        return hash_equals($this->sharedSecret, substr($authorization, 7));
    }
}
