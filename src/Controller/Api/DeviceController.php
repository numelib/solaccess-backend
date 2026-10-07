<?php

namespace App\Controller\Api;

use App\Config\LockStates;
use App\Entity\AccessDevice;
use App\Entity\AccessLog;
use App\Entity\Contact;
use App\Entity\User;
use App\Repository\CommandRepository;
use App\Service\DeviceConfigBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route("/api/device", name: "api_device_")]
final class DeviceController extends AbstractController
{
    #[Route("/config", name: "config", methods: ["GET"])]
    public function config(
        DeviceConfigBuilder $deviceConfigBuilder,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        /** @var AccessDevice $device */
        $device = $this->getUser();

        $device
            ->setLastSeenAt(new \DateTimeImmutable())
            ->setLastSyncAt(new \DateTimeImmutable());

        $entityManager->flush();

        return $this->json($deviceConfigBuilder->build($device));
    }

    #[Route("/heartbeat", name: "heartbeat", methods: ["POST"])]
    public function heartbeat(
        Request $request,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        /** @var AccessDevice $device */
        $device = $this->getUser();

        $payload = json_decode($request->getContent(), true) ?? [];

        $device
            ->setLastSeenAt(new \DateTimeImmutable())
            ->setLastIp($payload["ip"] ?? $request->getClientIp())
            ->setFirmwareVersion(
                $payload["firmwareVersion"] ?? $device->getFirmwareVersion(),
            );

        $entityManager->flush();

        return $this->json([
            "ok" => true,
            "serverTime" => new \DateTimeImmutable()->format(
                \DateTimeInterface::ATOM,
            ),
            "serverEpoch" => time(),
        ]);
    }

    #[Route("/logs", name: "logs", methods: ["POST"])]
    public function logs(
        Request $request,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        /** @var AccessDevice $device */
        $device = $this->getUser();

        $payload = json_decode($request->getContent(), true) ?? [];
        $items = is_array($payload["logs"] ?? null) ? $payload["logs"] : [];

        $accepted = 0;
        $rejected = 0;

        foreach ($items as $item) {
            if (!is_array($item)) {
                ++$rejected;
                continue;
            }

            try {
                $credential = null;
                $contact = null;
                $contactId =
                    isset($item["userId"]) && $item["userId"] != 0
                        ? $item["userId"]
                        : null;
                if ($contactId !== null) {
                    $c = $entityManager
                        ->getRepository(Contact::class)
                        ->find($contactId);
                    if ($c !== null) {
                        $contact = $c;
                    }
                }
                $log = new AccessLog()
                    ->setLock($device->getLock())
                    ->setOccurredAt(
                        isset($item["occurredAt"])
                            ? new \DateTimeImmutable(
                                (string) $item["occurredAt"],
                            )
                            : new \DateTimeImmutable(),
                    )
                    ->setType((string) ($item["type"] ?? "unknown"))
                    ->setSource((string) ($item["source"] ?? "unknown"))
                    ->setSuccess(
                        array_key_exists("success", $item)
                            ? (bool) $item["success"]
                            : null,
                    )
                    ->setReason(
                        isset($item["reason"])
                            ? (string) $item["reason"]
                            : null,
                    )
                    ->setContactIdentifier($contact)
                    ->setRawPayload($item);

                $entityManager->persist($log);
                ++$accepted;
            } catch (\Throwable) {
                ++$rejected;
            }
        }

        $device->setLastSeenAt(new \DateTimeImmutable());
        $device->setLastIp($request->getClientIp());
        $entityManager->flush();

        return $this->json([
            "ok" => true,
            "accepted" => $accepted,
            "rejected" => $rejected,
        ]);
    }

    // src/Controller/Api/DeviceStatusController.php

    #[Route("/status", name: "status", methods: ["POST"])]
    public function status(
        Request $request,
        EntityManagerInterface $em,
        CommandRepository $commandRepository,
    ): JsonResponse {
        /** @var AccessDevice $device */
        $device = $this->getUser();

        $data = json_decode($request->getContent(), true);

        $device->setLockState(LockStates::tryFrom($data["lockState"]));
        $device->setUptime($data["uptime"] ?? null);
        $device->setFreeHeap($data["freeHeap"] ?? null);
        $device->setLastStatusAt(new \DateTimeImmutable());

        // Efface la commande en attente — l'ESP32 confirme qu'elle a été exécutée
        $command = $commandRepository->findLastInProgressByDevice($device);
        if ($command !== null) {
            $command->setProcessedAt(new \DateTimeImmutable());
            $em->persist($command);
        }

        $em->flush();

        return new JsonResponse(["ok" => true]);
    }
}
