<?php

declare(strict_types=1);

namespace App\Service\Http;

use App\ServiceInterface\Http\AnalyticsErrorResponseFactoryInterface;
use App\ServiceInterface\Http\AnalyticsIdempotencyRequestSubscriberInterface;
use App\ServiceInterface\Http\AnalyticsIdempotencyStoreInterface;
use App\ServiceInterface\Http\TenantContextResolverInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class AnalyticsIdempotencyRequestSubscriber implements AnalyticsIdempotencyRequestSubscriberInterface
{
    /** @var list<string> */
    private const array WRITE_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function __construct(
        private readonly AnalyticsIdempotencyStoreInterface $store,
        private readonly AnalyticsErrorResponseFactoryInterface $errors,
        private readonly TenantContextResolverInterface $tenantResolver,
        private readonly bool $enabled = true,
        private readonly bool $required = false,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', -16],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest() || !$this->enabled) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('_route');
        if (!is_string($route) || !$this->supports($request->getMethod(), $route)) {
            return;
        }

        $startedAt = microtime(true);
        $key = trim((string) $request->headers->get('X-Idempotency-Key', ''));
        if ('' === $key) {
            if ($this->required) {
                $event->setResponse($this->errors->create(
                    $route,
                    'Analytics idempotency key is required.',
                    'analytics.idempotency.required',
                    Response::HTTP_BAD_REQUEST,
                    $startedAt,
                ));
            }

            return;
        }

        if (!$this->store->isValidKey($key)) {
            $response = $this->errors->create(
                $route,
                'Analytics idempotency key is invalid.',
                'analytics.idempotency.invalid_key',
                Response::HTTP_BAD_REQUEST,
                $startedAt,
                false,
                ['tenant' => $this->tenantResolver->resolve($request)],
            );
            $response->headers->set('X-Idempotency-Key', $key);
            $event->setResponse($response);

            return;
        }

        $tenant = $this->tenantResolver->resolve($request);
        $requestFingerprint = hash('sha256', implode('|', [
            $request->getMethod(),
            $route,
            $tenant,
            $request->getQueryString() ?? '',
            (string) $request->getContent(),
        ]));

        $decision = $this->store->begin($route, $tenant, $key, $requestFingerprint);
        $request->attributes->set('_analytics_idempotency_key', $key);
        $request->attributes->set('_analytics_idempotency_tenant', $tenant);
        $request->attributes->set('_analytics_idempotency_route', $route);
        $request->attributes->set('_analytics_idempotency_fingerprint', $requestFingerprint);

        switch ($decision['status']) {
            case 'new':
                $request->attributes->set('_analytics_idempotency_active', true);
                return;

            case 'replay':
                $response = $this->store->buildReplayResponse($decision['record'] ?? []);
                $response->headers->set('X-Idempotency-Key', $key);
                $response->headers->set('Idempotency-Status', 'replayed');
                $event->setResponse($response);
                return;

            case 'pending':
                $response = $this->errors->create(
                    $route,
                    'Analytics idempotent request is already in progress.',
                    'analytics.idempotency.in_progress',
                    Response::HTTP_CONFLICT,
                    $startedAt,
                    true,
                    ['tenant' => $tenant],
                );
                $response->headers->set('X-Idempotency-Key', $key);
                $event->setResponse($response);
                return;

            case 'conflict':
                $response = $this->errors->create(
                    $route,
                    'Analytics idempotency key was already used with a different request.',
                    'analytics.idempotency.conflict',
                    Response::HTTP_CONFLICT,
                    $startedAt,
                    false,
                    ['tenant' => $tenant],
                );
                $response->headers->set('X-Idempotency-Key', $key);
                $event->setResponse($response);
                return;
        }
    }

    private function supports(string $method, string $route): bool
    {
        return str_starts_with($route, 'analytics_') && in_array(strtoupper($method), self::WRITE_METHODS, true);
    }

}
