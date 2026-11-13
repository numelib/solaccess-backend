<?php

declare(strict_types=1);

namespace App\Controller\Api\Bridge;

use App\Exception\ContactOperationException;
use App\Exception\PortalBridgeAccessException;
use App\Service\Bridge\PortalBridgeResponse;
use App\Service\Bridge\PortalBridgeUserResolver;
use App\Service\Contacts\ContactManager;
use App\Service\Contacts\ContactViewBuilder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/** Simple transport Numelib : toutes les règles métier restent dans ContactManager. */
#[Route('/api/portal-bridge/solaccess/contacts', name: 'portal_bridge_solaccess_contacts_')]
final class ContactController
{
    public function __construct(
        private readonly PortalBridgeUserResolver $users,
        private readonly ContactManager $contacts,
        private readonly ContactViewBuilder $views,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        try {
            $user = $this->users->resolve($request);
            return PortalBridgeResponse::json([
                'items' => array_map(fn ($contact) => $this->views->build($contact, false),
                    $this->contacts->listFor($user)),
            ]);
        } catch (PortalBridgeAccessException $e) {
            return $this->accessError($e);
        }
    }

    #[Route('/{id<\d+>}', name: 'detail', methods: ['GET'])]
    public function detail(Request $request, int $id): JsonResponse
    {
        try {
            return PortalBridgeResponse::json($this->views->build(
                $this->contacts->findFor($this->users->resolve($request), $id), false,
            ));
        } catch (PortalBridgeAccessException $e) {
            return $this->accessError($e);
        } catch (ContactOperationException $e) {
            return PortalBridgeResponse::json($e->toPublicPayload(), $e->getHttpStatus());
        }
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $user = $this->users->resolve($request, write: true);
            $data = $this->decodeJson($request);
            $contact = $this->contacts->createFor($user, $data);
            return PortalBridgeResponse::json($this->views->build($contact, false), Response::HTTP_CREATED);
        } catch (PortalBridgeAccessException $e) {
            return $this->accessError($e);
        } catch (ContactOperationException $e) {
            return PortalBridgeResponse::json($e->toPublicPayload(), $e->getHttpStatus());
        }
    }

    #[Route('/{id<\d+>}', name: 'update', methods: ['PATCH'])]
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $user = $this->users->resolve($request, write: true);
            $data = $this->decodeJson($request);
            $contact = $this->contacts->updateFor($user, $id, $data);
            return PortalBridgeResponse::json($this->views->build($contact, false));
        } catch (PortalBridgeAccessException $e) {
            return $this->accessError($e);
        } catch (ContactOperationException $e) {
            return PortalBridgeResponse::json($e->toPublicPayload(), $e->getHttpStatus());
        }
    }

    #[Route('/{id<\d+>}', name: 'delete', methods: ['DELETE'])]
    public function delete(Request $request, int $id): Response
    {
        try {
            $user = $this->users->resolve($request, write: true);
            $this->contacts->deleteFor($user, $id);
            return PortalBridgeResponse::noContent();
        } catch (PortalBridgeAccessException $e) {
            return $this->accessError($e);
        } catch (ContactOperationException $e) {
            return PortalBridgeResponse::json($e->toPublicPayload(), $e->getHttpStatus());
        }
    }

    /** @return array<string, mixed> */
    private function decodeJson(Request $request): array
    {
        $contentType = strtolower(trim(explode(';', $request->headers->get('Content-Type', ''), 2)[0]));
        $body = $request->getContent();
        if ($contentType !== 'application/json' || strlen($body) > 4096) {
            throw new ContactOperationException('Invalid JSON request', 400, 'invalid_json');
        }
        try {
            $data = json_decode($body, false, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new ContactOperationException('Invalid JSON request', 400, 'invalid_json');
        }
        if (!$data instanceof \stdClass) {
            throw new ContactOperationException('Invalid JSON request', 400, 'invalid_json');
        }
        return (array) $data;
    }

    private function accessError(PortalBridgeAccessException $e): JsonResponse
    {
        return PortalBridgeResponse::json($e->toPublicPayload(), $e->getHttpStatus());
    }
}
