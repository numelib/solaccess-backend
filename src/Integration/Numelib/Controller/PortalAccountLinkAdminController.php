<?php

declare(strict_types=1);

namespace App\Integration\Numelib\Controller;

use App\Integration\Numelib\Exception\PortalAccountLinkException;
use App\Integration\Numelib\Exception\PortalBridgeAccessException;
use App\Integration\Numelib\Security\PortalBridgeAdminVerifier;
use App\Integration\Numelib\Service\PortalAccountLinkManager;
use App\Integration\Numelib\Service\PortalBridgeResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Intégration administrative Numelib facultative : des liaisons, jamais de User/ESP32.
 * Protection centrale dans PortalBridgeAdminGuardSubscriber + contrôle local.
 */
#[Route('/api/portal-bridge/solaccess/admin', name: 'portal_bridge_admin_')]
final class PortalAccountLinkAdminController
{
    public function __construct(
        private readonly PortalBridgeAdminVerifier $admins,
        private readonly PortalAccountLinkManager $links,
        private readonly LoggerInterface $logger,
    ) {
    }

    /** Aperçu limité d'un compte créé au préalable dans /admin. */
    #[Route('/users/{userId<\d+>}', name: 'user_preview', methods: ['GET'])]
    public function userPreview(Request $request, int $userId): JsonResponse
    {
        try {
            $this->admins->verify($request);
            return PortalBridgeResponse::json($this->links->candidateFor($userId));
        } catch (PortalBridgeAccessException|PortalAccountLinkException $e) {
            return PortalBridgeResponse::json($e->toPublicPayload(), $e->getHttpStatus());
        }
    }

    #[Route('/links/{externalId}', name: 'link_show', requirements: ['externalId' => 'rec[A-Za-z0-9_-]{4,61}'], methods: ['GET'])]
    public function show(Request $request, string $externalId): JsonResponse
    {
        try {
            $this->admins->verify($request);
            return PortalBridgeResponse::json($this->links->statusFor($externalId));
        } catch (PortalBridgeAccessException|PortalAccountLinkException $e) {
            return PortalBridgeResponse::json($e->toPublicPayload(), $e->getHttpStatus());
        }
    }

    #[Route('/links/{externalId}', name: 'link_create', requirements: ['externalId' => 'rec[A-Za-z0-9_-]{4,61}'], methods: ['POST'])]
    public function create(Request $request, string $externalId): JsonResponse
    {
        try {
            $adminId = $this->admins->verify($request);
            $data = $this->readJson($request, ['solaccessUserId']);
            if (!is_int($data['solaccessUserId']) || $data['solaccessUserId'] <= 0) {
                throw new PortalAccountLinkException('invalid_user_id', 422);
            }
            $result = $this->links->link($externalId, $data['solaccessUserId']);
            $this->logger->notice('Numelib/Solaccess association created', [
                'adminRecordId' => $adminId,
                'targetRecordId' => $externalId,
                'solaccessUserId' => $result['solaccessUserId'],
            ]);
            return PortalBridgeResponse::json($result, Response::HTTP_CREATED);
        } catch (PortalBridgeAccessException|PortalAccountLinkException $e) {
            return PortalBridgeResponse::json($e->toPublicPayload(), $e->getHttpStatus());
        }
    }

    #[Route('/links/{externalId}', name: 'link_update', requirements: ['externalId' => 'rec[A-Za-z0-9_-]{4,61}'], methods: ['PATCH'])]
    public function update(Request $request, string $externalId): JsonResponse
    {
        try {
            $adminId = $this->admins->verify($request);
            $data = $this->readJson($request, ['enabled']);
            if (!is_bool($data['enabled'])) {
                throw new PortalAccountLinkException('invalid_enabled', 422);
            }
            $result = $this->links->setEnabled($externalId, $data['enabled']);
            $this->logger->notice('Numelib/Solaccess association availability changed', [
                'adminRecordId' => $adminId,
                'targetRecordId' => $externalId,
                'solaccessUserId' => $result['solaccessUserId'],
                'enabled' => $result['enabled'],
            ]);
            return PortalBridgeResponse::json($result);
        } catch (PortalBridgeAccessException|PortalAccountLinkException $e) {
            return PortalBridgeResponse::json($e->toPublicPayload(), $e->getHttpStatus());
        }
    }

    #[Route('/links/{externalId}', name: 'link_delete', requirements: ['externalId' => 'rec[A-Za-z0-9_-]{4,61}'], methods: ['DELETE'])]
    public function delete(Request $request, string $externalId): Response
    {
        try {
            $adminId = $this->admins->verify($request);
            $status = $this->links->statusFor($externalId);
            $this->links->unlink($externalId);
            $this->logger->notice('Numelib/Solaccess association removed', [
                'adminRecordId' => $adminId,
                'targetRecordId' => $externalId,
                'solaccessUserId' => $status['solaccessUserId'] ?? null,
            ]);
            return PortalBridgeResponse::noContent();
        } catch (PortalBridgeAccessException|PortalAccountLinkException $e) {
            return PortalBridgeResponse::json($e->toPublicPayload(), $e->getHttpStatus());
        }
    }

    /** @param list<string> $expected
     * @return array<string, mixed>
     */
    private function readJson(Request $request, array $expected): array
    {
        $type = strtolower(trim(explode(';', $request->headers->get('Content-Type', ''), 2)[0]));
        $body = $request->getContent();
        if ($type !== 'application/json' || strlen($body) > 1024) {
            throw new PortalAccountLinkException('invalid_json', 400);
        }
        try {
            $value = json_decode($body, false, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new PortalAccountLinkException('invalid_json', 400);
        }
        if (!$value instanceof \stdClass) {
            throw new PortalAccountLinkException('invalid_json', 400);
        }
        $data = (array) $value;
        $keys = array_keys($data);
        sort($keys);
        sort($expected);
        if ($keys !== $expected) {
            throw new PortalAccountLinkException('invalid_fields', 422);
        }
        return $data;
    }
}
