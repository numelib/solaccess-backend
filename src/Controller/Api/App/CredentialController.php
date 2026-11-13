<?php

declare(strict_types=1);

namespace App\Controller\Api\App;

use App\Entity\User;
use App\Exception\CredentialOperationException;
use App\Service\Credentials\CredentialManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/** Adaptateur JWT historique : le métier est désormais dans CredentialManager. */
#[Route('/api/app/devices/{controllerId<\d+>}/credentials', name: 'api_app_credentials_')]
final class CredentialController extends AbstractController
{
    public function __construct(private readonly CredentialManager $credentials)
    {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(int $controllerId): JsonResponse
    {
        try {
            return $this->json(['items' => $this->credentials->listFor($this->requireUser(), $controllerId)]);
        } catch (CredentialOperationException $e) {
            return $this->json($e->toPublicPayload(), $e->getHttpStatus());
        }
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(int $controllerId, Request $request): JsonResponse
    {
        try {
            $data = $this->credentials->createFor($this->requireUser(), $controllerId, $this->decodeJson($request));
            return $this->json($data, 201);
        } catch (CredentialOperationException $e) {
            return $this->json($e->toPublicPayload(), $e->getHttpStatus());
        }
    }

    #[Route('/{credentialId}', name: 'update', methods: ['PATCH'])]
    public function update(int $controllerId, string $credentialId, Request $request): JsonResponse
    {
        try {
            return $this->json($this->credentials->updateFor(
                $this->requireUser(), $controllerId, $credentialId, $this->decodeJson($request),
            ));
        } catch (CredentialOperationException $e) {
            return $this->json($e->toPublicPayload(), $e->getHttpStatus());
        }
    }

    #[Route('/{credentialId}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $controllerId, string $credentialId): JsonResponse
    {
        try {
            $this->credentials->deleteFor($this->requireUser(), $controllerId, $credentialId);
            return new JsonResponse(null, 204);
        } catch (CredentialOperationException $e) {
            return $this->json($e->toPublicPayload(), $e->getHttpStatus());
        }
    }

    private function requireUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }
        return $user;
    }

    /** @return array<string, mixed> */
    private function decodeJson(Request $request): array
    {
        $payload = json_decode($request->getContent(), true);
        return is_array($payload) ? $payload : [];
    }
}
