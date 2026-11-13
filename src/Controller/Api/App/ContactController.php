<?php

declare(strict_types=1);

namespace App\Controller\Api\App;

use App\Entity\User;
use App\Exception\ContactOperationException;
use App\Service\Contacts\ContactManager;
use App\Service\Contacts\ContactViewBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Adaptateur JWT historique. La logique métier est dans ContactManager et
 * pourra être réutilisée par Api/Bridge avant le retrait définitif d'Api/App.
 */
#[Route('/api/app/contacts', name: 'api_app_contacts_')]
final class ContactController extends AbstractController
{
    public function __construct(
        private readonly ContactManager $contacts,
        private readonly ContactViewBuilder $views,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $actor = $this->requireUser();
        return $this->json([
            'items' => array_map(
                fn ($contact) => $this->views->build($contact, false),
                $this->contacts->listFor($actor),
            ),
        ]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $contact = $this->contacts->createFor($this->requireUser(), $this->decodeJson($request));
            return $this->json($this->views->build($contact, true), 201);
        } catch (ContactOperationException $e) {
            return $this->json($e->toPublicPayload(), $e->getHttpStatus());
        }
    }

    #[Route('/{id<\d+>}', name: 'detail', methods: ['GET'])]
    public function detail(int $id): JsonResponse
    {
        try {
            return $this->json($this->views->build($this->contacts->findFor($this->requireUser(), $id), true));
        } catch (ContactOperationException $e) {
            return $this->json($e->toPublicPayload(), $e->getHttpStatus());
        }
    }

    #[Route('/{id<\d+>}', name: 'update', methods: ['PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        try {
            $contact = $this->contacts->updateFor($this->requireUser(), $id, $this->decodeJson($request));
            return $this->json($this->views->build($contact, true));
        } catch (ContactOperationException $e) {
            return $this->json($e->toPublicPayload(), $e->getHttpStatus());
        }
    }

    #[Route('/{id<\d+>}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        try {
            $this->contacts->deleteFor($this->requireUser(), $id);
            return new JsonResponse(null, 204);
        } catch (ContactOperationException $e) {
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
