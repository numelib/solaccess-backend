<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\User;
use App\Security\PortalBridgeClientVerifier;
use App\Service\PortalBridgeSnapshot;
use App\Service\SolaccessPortalSessionManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use App\Security\UserChecker;

/**
 * API BFF uniquement serveur-vers-serveur : un unique contrôleur, toutes les
 * actions imposent le secret de passerelle. Aucun appel depuis le navigateur.
 * Le firewall JWT /api/app et le firewall ESP32 demeurent inchangés.
 */
#[Route('/api/portal-bridge/solaccess', name: 'portal_bridge_solaccess_')]
final class PortalBridgeController
{
    public function __construct(
        private readonly PortalBridgeClientVerifier $bridgeClient,
        private readonly SolaccessPortalSessionManager $sessions,
        private readonly PortalBridgeSnapshot $snapshot,
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UserChecker $userChecker,
        #[Target('portal_bridge_login')]
        private readonly RateLimiterFactoryInterface $loginLimiter,
        #[Target('portal_bridge_global')]
        private readonly RateLimiterFactoryInterface $globalLimiter,
    ) {
    }

    #[Route('/session', name: 'create_session', methods: ['POST'])]
    public function createSession(Request $request): JsonResponse
    {
        if (!$this->bridgeClient->isTrusted($request)) {
            return $this->json(['error' => 'unauthorized_bridge'], Response::HTTP_UNAUTHORIZED);
        }

        // Limite globale : une clé technique volée ne donne pas des essais illimités.
        if (!$this->globalLimiter->create('portal_numelib')->consume(1)->isAccepted()) {
            return $this->json(['error' => 'rate_limited'], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $body = json_decode($request->getContent(), true);
        if (!is_array($body) || array_is_list($body)) {
            return $this->json(['error' => 'invalid_request'], Response::HTTP_BAD_REQUEST);
        }

        $email = $body['email'] ?? null;
        $password = $body['password'] ?? null;
        $numelibUserId = $body['numelibUserId'] ?? null;
        $browserBinding = $body['browserBinding'] ?? null;

        if (!is_string($email) || strlen($email) > 180 || !filter_var($email, FILTER_VALIDATE_EMAIL)
            || !is_string($password) || $password === '' || strlen($password) > 4096
            || !is_string($numelibUserId) || !$this->validNumelibId($numelibUserId)
            || !is_string($browserBinding) || strlen($browserBinding) < 32 || strlen($browserBinding) > 256) {
            return $this->json(['error' => 'invalid_request'], Response::HTTP_BAD_REQUEST);
        }

        $limiterKey = hash('sha256', strtolower($email));
        if (!$this->loginLimiter->create($limiterKey)->consume(1)->isAccepted()) {
            return $this->json(['error' => 'rate_limited'], Response::HTTP_TOO_MANY_REQUESTS);
        }

        /** @var User|null $user */
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);

        // Même message pour compte inconnu, inactif ou mot de passe erroné.
        if ($user === null || $user->isActive() !== true || $user->getPassword() === null
            || !$this->passwordHasher->isPasswordValid($user, $password)) {
            return $this->json(['error' => 'invalid_credentials'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $this->userChecker->checkPreAuth($user);
            $this->userChecker->checkPostAuth($user);
        } catch (AccountStatusException) {
            return $this->json(['error' => 'invalid_credentials'], Response::HTTP_UNAUTHORIZED);
        }

        $handle = $this->sessions->create($user, $numelibUserId, $browserBinding);

        // "handle" uniquement destiné au serveur Teable, qui le place dans
        // un cookie sécurisé. Ne jamais transmettre cette réponse au navigateur.
        return $this->json([
            'handle' => $handle,
            'symfonyUserId' => $user->getId(),
            'expiresIn' => 1800,
        ], Response::HTTP_CREATED);
    }

    #[Route('/session', name: 'inspect_session', methods: ['GET'])]
    public function inspectSession(Request $request): JsonResponse
    {
        if (!$this->bridgeClient->isTrusted($request)) {
            return $this->json(['error' => 'unauthorized_bridge'], Response::HTTP_UNAUTHORIZED);
        }

        $session = $this->sessions->resolve(...$this->sessionIdentity($request));
        if ($session === null) {
            return $this->json(['error' => 'session_expired'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'connected' => true,
            'symfonyUserId' => $session->getSolaccessUser()->getId(),
            'expiresAt' => $session->getExpiresAt()->format(\DateTimeInterface::ATOM),
        ]);
    }

    #[Route('/snapshot', name: 'snapshot', methods: ['GET'])]
    public function snapshot(Request $request): JsonResponse
    {
        if (!$this->bridgeClient->isTrusted($request)) {
            return $this->json(['error' => 'unauthorized_bridge'], Response::HTTP_UNAUTHORIZED);
        }

        $session = $this->sessions->resolve(...$this->sessionIdentity($request));
        if ($session === null) {
            return $this->json(['error' => 'session_expired'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json($this->snapshot->forUser($session->getSolaccessUser()));
    }

    #[Route('/session', name: 'delete_session', methods: ['DELETE'])]
    public function deleteSession(Request $request): JsonResponse
    {
        if (!$this->bridgeClient->isTrusted($request)) {
            return $this->json(['error' => 'unauthorized_bridge'], Response::HTTP_UNAUTHORIZED);
        }

        // Révocation idempotente. Jamais de révocation d'une autre identité.
        $this->sessions->revoke(...$this->sessionIdentity($request));
        return $this->json(['disconnected' => true]);
    }

    /** @return array{string, string, string} */
    private function sessionIdentity(Request $request): array
    {
        return [
            $request->headers->get('X-Solaccess-Session', ''),
            $request->headers->get('X-Numelib-User-Id', ''),
            $request->headers->get('X-Solaccess-Binding', ''),
        ];
    }

    private function validNumelibId(string $id): bool
    {
        return preg_match('/\A[A-Za-z0-9_-]{2,64}\z/', $id) === 1;
    }

    /** @param array<string, mixed> $payload */
    private function json(array $payload, int $status = Response::HTTP_OK): JsonResponse
    {
        $response = new JsonResponse($payload, $status);
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('Pragma', 'no-cache');
        return $response;
    }
}
