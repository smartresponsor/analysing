<?php

declare(strict_types=1);

namespace App\Analysing\EventSubscriber\Http;

use App\Analysing\FactoryInterface\Http\AnalyticsErrorResponseFactoryInterface;
use App\Analysing\Resolver\Http\AnalyticsVendorContextResolverInterface;
use App\Analysing\ServiceInterface\Http\AnalyticsIdempotencyStoreInterface;
use Symfony\Component\HttpFoundation\Request;
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
        if ($this->handleMissingKey($event, $route, $key, $startedAt)) {
            return;
        }

        if ($this->handleInvalidKey($event, $route, $key, $startedAt)) {
            return;
        }

        $vendor = $this->vendorResolver->resolve($request);
        $requestFingerprint = $this->fingerprint($request, $route, $vendor);
        $decision = $this->store->begin($route, $vendor, $key, $requestFingerprint);

        $this->attachRequestContext($request, $route, $vendor, $key, $requestFingerprint);
        $this->applyDecision($event, $decision, $route, $vendor, $key, $startedAt);
    }

    private function handleMissingKey(RequestEvent $event, string $route, string $key, float $startedAt): bool
    {
        if ('' !== $key) {
            return false;
        }

        if ($this->required) {
            $event->setResponse($this->errors->create(
                $route,
                'Analytics idempotency key is required.',
                'analytics.idempotency.required',
                Response::HTTP_BAD_REQUEST,
                $startedAt,
            ));
        }

        return true;
    }

    private function handleInvalidKey(RequestEvent $event, string $route, string $key, float $startedAt): bool
    {
        if ($this->store->isValidKey($key)) {
            return false;
        }

        $response = $this->errors->create(
            $route,
            'Analytics idempotency key is invalid.',
            'analytics.idempotency.invalid_key',
            Response::HTTP_BAD_REQUEST,
            $startedAt,
            false,
            ['vendor' => $this->vendorResolver->resolve($event->getRequest())],
        );
        $response->headers->set('X-Idempotency-Key', $key);
        $event->setResponse($response);

        return true;
    }

    private function fingerprint(Request $request, string $route, string $vendor): string
    {
        return hash('sha256', implode('|', [
            $request->getMethod(),
            $route,
            $vendor,
            $request->getQueryString() ?? '',
            (string) $request->getContent(),
        ]));
    }

    private function attachRequestContext(
        Request $request,
        string $route,
        string $vendor,
        string $key,
        string $requestFingerprint,
    ): void {
        $request->attributes->set('_analytics_idempotency_key', $key);
        $request->attributes->set('_analytics_idempotency_vendor', $vendor);
        $request->attributes->set('_analytics_idempotency_route', $route);
        $request->attributes->set('_analytics_idempotency_fingerprint', $requestFingerprint);
    }

    /** @param array{status: 'new'|'replay'|'conflict'|'pending', record?: array<string,mixed>} $decision */
    private function applyDecision(
        RequestEvent $event,
        array $decision,
        string $route,
        string $vendor,
        string $key,
        float $startedAt,
    ): void {
        switch ($decision['status']) {
            case 'new':
                $event->getRequest()->attributes->set('_analytics_idempotency_active', true);

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
