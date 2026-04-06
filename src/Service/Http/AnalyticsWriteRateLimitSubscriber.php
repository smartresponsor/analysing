<?php

declare(strict_types=1);

namespace App\Service\Http;

use App\ValueObject\Http\AnalyticsRateLimitDecision;
use App\ServiceInterface\Http\AnalyticsErrorResponseFactoryInterface;
use App\ServiceInterface\Http\AnalyticsRouteRateLimiterInterface;
use App\ServiceInterface\Http\AnalyticsWriteRateLimitSubscriberInterface;
use App\ServiceInterface\Http\TenantContextInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class AnalyticsWriteRateLimitSubscriber implements AnalyticsWriteRateLimitSubscriberInterface
{
    /** @var list<string> */
    private const array WRITE_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function __construct(
        private readonly AnalyticsRouteRateLimiterInterface $limiter,
        private readonly LoggerInterface $logger,
        private readonly AnalyticsErrorResponseFactoryInterface $errors,
        private readonly TenantContextInterface $tenantContext,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', -24],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest() || !$this->limiter->isEnabled()) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('_route');
        if (!is_string($route) || !$this->supports($request->getMethod(), $route)) {
            return;
        }

        $claims = $request->attributes->get('_analytics_token_claims');
        if (is_array($claims)) {
            $scope = $claims['scope'] ?? null;
            if (is_array($scope) && (($scope['admin'] ?? false) === true)) {
                return;
            }
        }

        $decision = $this->limiter->consume($route, $this->resolveScope($request));
        $request->attributes->set('_analytics_rate_limit_decision', $decision);

        if ($decision->allowed) {
            return;
        }

        $startedAt = microtime(true);
        $response = $this->errors->create(
            $route,
            'Analytics write rate limit exceeded.',
            'analytics.rate_limit.exceeded',
            Response::HTTP_TOO_MANY_REQUESTS,
            $startedAt,
            true,
            [
                'rate_limit_limit' => $decision->limit,
                'rate_limit_remaining' => $decision->remaining,
                'rate_limit_reset_at' => (new \DateTimeImmutable('@'.$decision->resetAt))->setTimezone(new \DateTimeZone('UTC'))->format(DATE_ATOM),
                'retry_after_seconds' => $decision->retryAfterSeconds,
                'rate_limit_scope' => $decision->scope,
                'rate_limit_mode' => $decision->mode,
            ],
        );

        $this->attachHeaders($response, $decision);
        $response->headers->set('Retry-After', (string) $decision->retryAfterSeconds);

        $this->logger->warning('Analytics write request rejected by rate limiter.', [
            'route' => $route,
            'scope' => $decision->scope,
            'limit' => $decision->limit,
            'retry_after_seconds' => $decision->retryAfterSeconds,
        ]);

        $event->setResponse($response);
    }

    private function supports(string $method, string $route): bool
    {
        return str_starts_with($route, 'analytics_') && in_array(strtoupper($method), self::WRITE_METHODS, true);
    }

    private function resolveScope(Request $request): string
    {
        $tokenSubject = $request->attributes->get('_analytics_token_subject');
        if (is_string($tokenSubject) && '' !== trim($tokenSubject)) {
            return 'subject:'.trim($tokenSubject);
        }

        $tenant = trim($this->tenantContext->current());
        if ('' !== $tenant && 'public' !== $tenant) {
            return 'tenant:'.$tenant;
        }

        $ip = trim((string) ($request->getClientIp() ?? 'unknown'));

        return 'ip:'.('' !== $ip ? $ip : 'unknown');
    }

    private function attachHeaders(Response $response, AnalyticsRateLimitDecision $decision): void
    {
        $response->headers->set('X-RateLimit-Limit', (string) $decision->limit);
        $response->headers->set('X-RateLimit-Remaining', (string) $decision->remaining);
        $response->headers->set('X-RateLimit-Reset', (string) $decision->resetAt);
    }
}
