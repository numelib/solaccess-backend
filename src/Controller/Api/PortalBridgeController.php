<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\SolaccessPortalAccountLink;
use App\Security\PortalBridgeClientVerifier;
use App\Security\UserChecker;
use App\Service\PortalBridgeSnapshot;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccountStatusException;

/**
 * Lecture serveur-vers-serveur exclusivement.
 * Le secret technique identifie l'application appelante ; l'ID Numelib est
 * attesté par le serveur du portail, qui DOIT vérifier la session OTP à
 * chaque requête avant de transmettre cet ID.
 */
#[Route('/api/portal-bridge/solaccess', name: 'portal_bridge_solaccess_')]
final class PortalBridgeController
{
    private const CLIENT_ID = 'numelib';

    public function __construct(
        private readonly PortalBridgeClientVerifier $bridgeClient,
        private readonly PortalBridgeSnapshot $snapshot,
        private readonly EntityManagerInterface $em,
        private readonly UserChecker $userChecker,
    ) {
    }

    #[Route('/snapshot', name: 'snapshot', methods: ['GET'])]
    public function snapshot(Request $request): JsonResponse
    {
        if (!$this->bridgeClient->isTrusted($request)) {
            return $this->json(['error' => 'unauthorized_bridge'], Response::HTTP_UNAUTHORIZED);
        }

        $externalUserId = $request->headers->get('X-Numelib-User-Id', '');
        if (!is_string($externalUserId) || !$this->isValidNumelibUserId($externalUserId)) {
            return $this->json(['error' => 'invalid_identity'], Response::HTTP_BAD_REQUEST);
        }

        /** @var SolaccessPortalAccountLink|null $link */
        $link = $this->em->getRepository(SolaccessPortalAccountLink::class)->findOneBy([
            'clientId' => self::CLIENT_ID,
            'externalUserId' => $externalUserId,
        ]);

        if ($link === null) {
            return $this->json(['error' => 'solaccess_not_linked'], Response::HTTP_NOT_FOUND);
        }
        if (!$link->isEnabled()) {
            return $this->json(['error' => 'solaccess_disabled'], Response::HTTP_FORBIDDEN);
        }

        $user = $link->getSolaccessUser();
        if ($user->isActive() !== true) {
            return $this->json(['error' => 'solaccess_disabled'], Response::HTTP_FORBIDDEN);
        }
        try {
            $this->userChecker->checkPreAuth($user);
            $this->userChecker->checkPostAuth($user);
        } catch (AccountStatusException) {
            return $this->json(['error' => 'solaccess_disabled'], Response::HTTP_FORBIDDEN);
        }

        // forUser() filtre toujours les contrôleurs par user et les contacts
        // par manager. Aucun champ PIN/UID/secret ESP32 dans cette projection.
        return $this->json($this->snapshot->forUser($user));
    }

    private function isValidNumelibUserId(string $id): bool
    {
        // Identifiant de record Teable (rec...), jamais un e-mail ni un ID choisi
        // dans une requête client. L'identité est déduite de la session OTP serveur.
        return preg_match('/\Arec[A-Za-z0-9_-]{4,61}\z/D', $id) === 1;
    }

    /** @param array<string, mixed> $payload */
    private function json(array $payload, int $status = Response::HTTP_OK): JsonResponse
    {
        $response = new JsonResponse($payload, $status);
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Vary', 'Authorization, X-Numelib-User-Id');
        return $response;
    }
}
