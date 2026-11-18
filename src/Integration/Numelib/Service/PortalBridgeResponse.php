<?php

declare(strict_types=1);

namespace App\Integration\Numelib\Service;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/** Réponses non cachables pour un bridge dont les identités varient par requête. */
final class PortalBridgeResponse
{
    /** @param array<string, mixed> $payload */
    public static function json(array $payload, int $status = Response::HTTP_OK): JsonResponse
    {
        $response = new JsonResponse($payload, $status);
        return self::headers($response);
    }

    public static function noContent(): Response
    {
        return self::headers(new Response('', Response::HTTP_NO_CONTENT));
    }

    /** @template T of Response
     * @param T $response
     * @return T
     */
    private static function headers(Response $response): Response
    {
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Vary', 'Authorization, X-Numelib-User-Id, X-Numelib-Admin-User-Id');
        return $response;
    }
}
