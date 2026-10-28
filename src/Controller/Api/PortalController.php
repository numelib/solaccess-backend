<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\AccessDeviceRepository;
use App\Service\App\AccessDeviceViewBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/app', name: 'api_app_')]
final class PortalController extends AbstractController
{
    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['message' => 'Unauthenticated'], 401);
        }

        return $this->json([
            'id' => $user->getId(),
            'identifier' => $user->getUserIdentifier(),
            'roles' => $user->getRoles(),
        ]);
    }

    #[Route('/controllers', name: 'controllers', methods: ['GET'])]
    public function controllers(
        AccessDeviceRepository $accessDeviceRepository,
        AccessDeviceViewBuilder $viewBuilder,
    ): JsonResponse {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['message' => 'Unauthenticated'], 401);
        }

        // On filtre directement en base par propriétaire.
        $devices = $accessDeviceRepository->findBy(
            ['user' => $user],
            ['name' => 'ASC'],
        );

        return $this->json([
            'items' => array_map(
                static fn ($device) => $viewBuilder->buildListItem($device)->toArray(),
                $devices,
            ),
        ]);
    }

    #[Route('/controllers/{id<\d+>}', name: 'controller', methods: ['GET'])]
    public function controller(
        int $id,
        AccessDeviceRepository $accessDeviceRepository,
        AccessDeviceViewBuilder $viewBuilder,
    ): JsonResponse {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['message' => 'Unauthenticated'], 401);
        }

        // Recherche ID + owner en une requête. Si le device appartient à un autre
        // compte, il est volontairement indistinguable d'un ID inexistant.
        $device = $accessDeviceRepository->findOneBy([
            'id' => $id,
            'user' => $user,
        ]);

        if ($device === null) {
            throw $this->createNotFoundException('Controller not found');
        }

        return $this->json($viewBuilder->buildDetail($device)->toArray());
    }
}
