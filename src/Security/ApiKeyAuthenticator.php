<?php

namespace App\Security;

use App\Repository\AccessDeviceRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

final class ApiKeyAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    public function __construct(
        private readonly AccessDeviceRepository $deviceRepository,
        private readonly ApiKeyManager $apiKeyManager,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return str_starts_with($request->getPathInfo(), '/api/device');
    }

    public function authenticate(Request $request): Passport
    {
        $authorization = $request->headers->get('Authorization');

        if (!$authorization || !preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
            throw new AuthenticationException('Missing bearer token.');
        }

        $token = trim($matches[1]);
        $parsed = $this->apiKeyManager->parseToken($token);

        if (null === $parsed) {
            throw new AuthenticationException('Malformed API token.');
        }

        $device = $this->deviceRepository->findOneEnabledByTokenPublicId($parsed['publicId']);

        if (!$device) {
            throw new AuthenticationException('Unknown device token.');
        }

        if (!$this->apiKeyManager->verifySecret($parsed['secret'], $device->getTokenHash())) {
            throw new AuthenticationException('Invalid device token.');
        }

        return new SelfValidatingPassport(
            new UserBadge($device->getUserIdentifier(), fn () => $device)
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'ok' => false,
            'error' => 'unauthorized',
            'message' => $exception->getMessage(),
        ], Response::HTTP_UNAUTHORIZED);
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new JsonResponse([
            'ok' => false,
            'error' => 'authentication_required',
        ], Response::HTTP_UNAUTHORIZED);
    }
}