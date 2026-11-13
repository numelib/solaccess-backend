<?php

namespace App\Controller\Api\App;

use App\Entity\User;
use App\Repository\AccessDeviceRepository;
use App\Service\AppControllerViewBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/app/devices', name: 'api_app_devices_')]
final class DeviceController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(
        AccessDeviceRepository $devices,
        AppControllerViewBuilder $viewBuilder,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        $items = array_map(
            $viewBuilder->buildListItem(...),
            $devices->findAllForUser($user),
        );

        return $this->json(['items' => $items]);
    }

    #[Route('/{id<\\d+>}', name: 'detail', methods: ['GET'])]
    public function detail(
        int $id,
        AccessDeviceRepository $devices,
        AppControllerViewBuilder $viewBuilder,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        // Important : la sécurité est appliquée dans la requête elle-même.
        // Un device appartenant à un autre compte est donc indistinguable
        // d'un identifiant inexistant (404).
        $device = $devices->findOneForUser($id, $user);

        if ($device === null) {
            throw $this->createNotFoundException();
        }

        return $this->json($viewBuilder->buildDetail($device));
    }
}
