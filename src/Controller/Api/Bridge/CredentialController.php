<?php

declare(strict_types=1);

namespace App\Controller\Api\Bridge;

use App\Exception\CredentialOperationException;
use App\Exception\PortalBridgeAccessException;
use App\Service\Bridge\PortalBridgeResponse;
use App\Service\Bridge\PortalBridgeUserResolver;
use App\Service\Credentials\CredentialManager;
use App\Service\Contacts\ContactManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/** API externe Numelib : acteur Symfony résolu via liaison permanente à chaque opération. */
#[Route('/api/portal-bridge/solaccess/devices/{controllerId<\d+>}/credentials', name: 'portal_bridge_solaccess_credentials_')]
final class CredentialController
{
    public function __construct(
        private readonly PortalBridgeUserResolver $users,
        private readonly CredentialManager $credentials,
        private readonly ContactManager $contacts,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request, int $controllerId): JsonResponse
    {
        try {
            $user = $this->users->resolve($request);
            return PortalBridgeResponse::json(['items' => $this->publicItems($user, $controllerId)]);
        } catch (PortalBridgeAccessException $e) {
            return PortalBridgeResponse::json($e->toPublicPayload(), $e->getHttpStatus());
        } catch (CredentialOperationException $e) {
            return PortalBridgeResponse::json($e->toPublicPayload(), $e->getHttpStatus());
        }
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, int $controllerId): JsonResponse
    {
        try {
            $user = $this->users->resolve($request, credentialWrite: true);
            $payload = $this->decodeJson($request, ['type', 'contactId', 'pin', 'uid', 'enablingAt', 'disablingAt']);
            if (isset($payload['type']) && !in_array($payload['type'], ['pin', 'nfc'], true)) {
                throw new CredentialOperationException('type must be pin or nfc', 422);
            }
            if (($payload['type'] ?? null) === 'pin' && array_key_exists('uid', $payload)
                || ($payload['type'] ?? null) === 'nfc' && array_key_exists('pin', $payload)) {
                throw new CredentialOperationException('Unexpected credential field', 422);
            }
            $data = $this->credentials->createFor($user, $controllerId, $payload);
            return PortalBridgeResponse::json($this->project($data, $this->ownedContactIds($user)), Response::HTTP_CREATED);
        } catch (PortalBridgeAccessException $e) {
            return PortalBridgeResponse::json($e->toPublicPayload(), $e->getHttpStatus());
        } catch (CredentialOperationException $e) {
            return PortalBridgeResponse::json($e->toPublicPayload(), $e->getHttpStatus());
        }
    }

    #[Route('/{credentialId}', name: 'update', methods: ['PATCH'])]
    public function update(Request $request, int $controllerId, string $credentialId): JsonResponse
    {
        try {
            $user = $this->users->resolve($request, credentialWrite: true);
            $payload = $this->decodeJson($request, ['contactId', 'pin', 'uid', 'enablingAt', 'disablingAt']);
            if (str_starts_with($credentialId, 'pin-') && array_key_exists('uid', $payload)
                || str_starts_with($credentialId, 'nfc-') && array_key_exists('pin', $payload)) {
                throw new CredentialOperationException('Unexpected credential field', 422);
            }
            $data = $this->credentials->updateFor($user, $controllerId, $credentialId, $payload);
            return PortalBridgeResponse::json($this->project($data, $this->ownedContactIds($user)));
        } catch (PortalBridgeAccessException $e) {
            return PortalBridgeResponse::json($e->toPublicPayload(), $e->getHttpStatus());
        } catch (CredentialOperationException $e) {
            return PortalBridgeResponse::json($e->toPublicPayload(), $e->getHttpStatus());
        }
    }

    #[Route('/{credentialId}', name: 'delete', methods: ['DELETE'])]
    public function delete(Request $request, int $controllerId, string $credentialId): Response
    {
        try {
            $user = $this->users->resolve($request, credentialWrite: true);
            $this->credentials->deleteFor($user, $controllerId, $credentialId);
            return PortalBridgeResponse::noContent();
        } catch (PortalBridgeAccessException $e) {
            return PortalBridgeResponse::json($e->toPublicPayload(), $e->getHttpStatus());
        } catch (CredentialOperationException $e) {
            return PortalBridgeResponse::json($e->toPublicPayload(), $e->getHttpStatus());
        }
    }

    /**
     * Le service métier peut manipuler les credentials bruts, jamais la réponse HTTP.
     * Nous limitons strictement les champs visibles, sans PIN, UID, salt ou hash.
     * @return array<string, mixed>
     */
    private function project(array $item, array $ownedContactIds): array
    {
        $contactId = $item['contact']['id'] ?? null;
        // Ne pas exposer les associations à des contacts hors périmètre.
        return [
            'id' => $item['id'],
            'type' => $item['type'],
            'contactId' => is_int($contactId) && isset($ownedContactIds[$contactId]) ? $contactId : null,
            'controllerId' => $item['controllerId'] ?? null,
            'enabled' => $item['enabled'] ?? null,
            'enablingAt' => $item['enablingAt'] ?? null,
            'disablingAt' => $item['disablingAt'] ?? null,
        ];
    }

    /** @return list<array<string, mixed>> */
    private function publicItems(\App\Entity\User $user, int $controllerId): array
    {
        $items = $this->credentials->listFor($user, $controllerId);
        $ownedContactIds = $this->ownedContactIds($user);
        return array_map(fn (array $item): array => $this->project($item, $ownedContactIds), $items);
    }

    /** @return array<int, true> */
    private function ownedContactIds(\App\Entity\User $user): array
    {
        $ids = [];
        foreach ($this->contacts->listFor($user) as $contact) {
            $ids[$contact->getId()] = true;
        }
        return $ids;
    }

    /** @param list<string> $allowed
     *  @return array<string, mixed>
     */
    private function decodeJson(Request $request, array $allowed): array
    {
        $type = strtolower(trim(explode(';', $request->headers->get('Content-Type', ''), 2)[0]));
        $body = $request->getContent();
        if ($type !== 'application/json' || strlen($body) > 4096) {
            throw new CredentialOperationException('Invalid JSON request', 400, 'invalid_json');
        }
        try {
            $decoded = json_decode($body, false, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new CredentialOperationException('Invalid JSON request', 400, 'invalid_json');
        }
        if (!$decoded instanceof \stdClass) {
            throw new CredentialOperationException('Invalid JSON request', 400, 'invalid_json');
        }
        $payload = (array) $decoded;
        if (array_diff(array_keys($payload), $allowed) !== []) {
            throw new CredentialOperationException('Unexpected credential field', 422);
        }
        return $payload;
    }
}
