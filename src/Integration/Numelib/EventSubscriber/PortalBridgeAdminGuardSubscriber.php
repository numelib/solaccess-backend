<?php

declare(strict_types=1);

namespace App\Integration\Numelib\EventSubscriber;

use App\Integration\Numelib\Exception\PortalBridgeAccessException;
use App\Integration\Numelib\Security\PortalBridgeAdminVerifier;
use App\Integration\Numelib\Service\PortalBridgeResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/** Protection centralisée, y compris pour une future route créée sous /admin/. */
final class PortalBridgeAdminGuardSubscriber implements EventSubscriberInterface
{
    private const PATH = '/api/portal-bridge/solaccess/admin';

    public function __construct(private readonly PortalBridgeAdminVerifier $admins)
    {
    }

    /** @return array<string, array{string, int}> */
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['guard', 16]];
    }

    public function guard(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }
        $request = $event->getRequest();
        $path = $request->getPathInfo();
        if ($path !== self::PATH && !str_starts_with($path, self::PATH . '/')) {
            return;
        }

        try {
            $request->attributes->set('_numelib_verified_admin_id', $this->admins->verify($request));
        } catch (PortalBridgeAccessException $e) {
            $event->setResponse(PortalBridgeResponse::json($e->toPublicPayload(), $e->getHttpStatus()));
        }
    }
}
