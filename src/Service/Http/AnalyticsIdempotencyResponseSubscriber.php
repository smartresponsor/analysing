<?php

declare(strict_types=1);

namespace App\Service\Http;

use App\ServiceInterface\Http\AnalyticsIdempotencyResponseSubscriberInterface;
use App\ServiceInterface\Http\AnalyticsIdempotencyStoreInterface;
use App\ServiceInterface\Http\TenantContextInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class AnalyticsIdempotencyResponseSubscriber implements AnalyticsIdempotencyResponseSubscriberInterface
{
    public function __construct(
        private readonly AnalyticsIdempotencyStoreInterface $store,
        private readonly bool $enabled = true,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest() || !$this->enabled) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();

        $active = $request->attributes->get('_analytics_idempotency_active');
        $key = $request->attributes->get('_analytics_idempotency_key');
        $tenant = $request->attributes->get('_analytics_idempotency_tenant');
        $route = $request->attributes->get('_analytics_idempotency_route');
        $fingerprint = $request->attributes->get('_analytics_idempotency_fingerprint');

        if (true !== $active || !is_string($key) || !is_string($tenant) || !is_string($route) || !is_string($fingerprint)) {
            return;
        }

        $this->store->finalize($route, $tenant, $key, $fingerprint, $response);
        $response->headers->set('X-Idempotency-Key', $key);
        if (!$response->headers->has('Idempotency-Status')) {
            $response->headers->set('Idempotency-Status', 'created');
        }
    }
}
