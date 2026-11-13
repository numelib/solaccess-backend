<?php

declare(strict_types=1);

namespace App\Controller\Api\Bridge;

use App\Exception\PortalBridgeAccessException;
use App\Service\Bridge\PortalBridgeResponse;
use App\Service\Bridge\PortalBridgeUserResolver;
use App\Service\PortalBridgeSnapshot;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/** Vue limitée à la lecture des ressources de l'utilisateur Numelib associé. */
#[Route('/api/portal-bridge/solaccess', name: 'portal_bridge_solaccess_')]
final class PortalBridgeController
{
    public function __construct(
        private readonly PortalBridgeUserResolver $users,
        private readonly PortalBridgeSnapshot $snapshot,
    ) {
    }

    #[Route('/snapshot', name: 'snapshot', methods: ['GET'])]
    public function snapshot(Request $request): JsonResponse
    {
        try {
            return PortalBridgeResponse::json($this->snapshot->forUser($this->users->resolve($request)));
        } catch (PortalBridgeAccessException $e) {
            return PortalBridgeResponse::json($e->toPublicPayload(), $e->getHttpStatus());
        }
    }
}
