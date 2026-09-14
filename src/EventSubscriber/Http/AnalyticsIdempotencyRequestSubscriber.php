<?php

declare(strict_types=1);

namespace App\Analysing\EventSubscriber\Http;

use App\Analysing\FactoryInterface\Http\AnalyticsErrorResponseFactoryInterface;
use App\Analysing\Resolver\Http\AnalyticsVendorContextResolverInterface;
use App\Analysing\ServiceInterface\Http\AnalyticsIdempotencyStoreInterface;
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
        private readonly AnalyticsVendorContextResolverInterface $vendorResolver,
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
                ['vendor' => $this->vendorResolver->resolve($request)],
            );
            $response->headers->set('X-Idempotency-Key', $key);
            $event->setResponse($response);

            return;
        }

        $vendor = $this->vendorResolver->resolve($request);
        $requestFingerprint = hash('sha256', implode('|', [
            $request->getMethod(),
            $route,
            $vendor,
            $request->getQueryString() ?? '',
            (string) $request->getContent(),
        ]));

        $decision = $this->store->begin($route, $vendor, $key, $requestFingerprint);
        $request->attributes->set('_analytics_idempotency_key', $key);
        $request->attributes->set('_analytics_idempotency_vendor', $vendor);
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
                    ['vendor' => $vendor],
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
                    ['vendor' => $vendor],
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
