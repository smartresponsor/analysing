<?php

declare(strict_types=1);

namespace App\Analysing\EventSubscriber\Http;

use App\Analysing\FactoryInterface\Http\AnalyticsErrorResponseFactoryInterface;
use App\Analysing\Resolver\Http\AnalyticsVendorContextResolverInterface;
use App\Analysing\ServiceInterface\AnalyticsTokenServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class AnalyticsRequestAuthSubscriber implements AnalyticsRequestAuthSubscriberInterface
{
    /** @var list<string> */
    private const array ALWAYS_PUBLIC_ROUTES = [
        'analytics_status',
        'analytics_health',
    ];

    /** @var list<string> */
    private const array PUBLIC_READ_ROUTES = [
        'analytics_metrics',
        'analytics_dashboard_kpi',
        'analytics_dashboard_timeseries',
        'analytics_dashboard_top_vendors',
        'analytics_dashboard_page',
    ];

    public function __construct(
        private readonly AnalyticsTokenServiceInterface $tokens,
        private readonly LoggerInterface $logger,
        private readonly AnalyticsErrorResponseFactoryInterface $errors,
        private readonly AnalyticsVendorContextResolverInterface $vendorResolver,
        private readonly bool $required = false,
        private readonly bool $publicRead = true,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 0],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('_route');
        if (!is_string($route) || '' === $route || !str_starts_with($route, 'analytics_')) {
            return;
        }

        if (in_array($route, self::ALWAYS_PUBLIC_ROUTES, true)) {
            return;
        }

        if (!$this->required) {
            return;
        }

        if ($this->publicRead && $this->isSafePublicReadRoute($request, $route)) {
            return;
        }

        $startedAt = microtime(true);
        $token = $this->extractToken($request);
        if (null === $token) {
            $event->setResponse($this->reject(
                'Analytics authorization token is required.',
                'analytics.auth.missing_token',
                Response::HTTP_UNAUTHORIZED,
                $route,
                $startedAt,
            ));

            return;
        }

        $claims = $this->tokens->verify($token);
        if ([] === $claims) {
            $event->setResponse($this->reject(
                'Analytics authorization token is invalid or expired.',
                'analytics.auth.invalid_token',
                Response::HTTP_UNAUTHORIZED,
                $route,
                $startedAt,
            ));

            return;
        }

        $scope = $claims['scope'] ?? [];
        if (!is_array($scope)) {
            $event->setResponse($this->reject(
                'Analytics authorization scope is invalid.',
                'analytics.auth.invalid_scope',
                Response::HTTP_FORBIDDEN,
                $route,
                $startedAt,
            ));

            return;
        }

        $scope = $this->normalizeAssociativeArray($scope);

        if (!$this->isRouteAllowed($route, $scope)) {
            $event->setResponse($this->reject(
                'Analytics authorization scope does not allow this route.',
                'analytics.auth.route_denied',
                Response::HTTP_FORBIDDEN,
                $route,
                $startedAt,
            ));

            return;
        }

        if (!$this->isVendorAllowed($request, $scope)) {
            $event->setResponse($this->reject(
                'Analytics authorization vendor does not match the request.',
                'analytics.auth.vendor_mismatch',
                Response::HTTP_FORBIDDEN,
                $route,
                $startedAt,
            ));

            return;
        }

        $request->attributes->set('_analytics_token_claims', $claims);
        if (isset($scope['vendor']) && is_scalar($scope['vendor'])) {
            $request->attributes->set('_analytics_token_vendor', trim((string) $scope['vendor']));
        }
        if (isset($scope['subject']) && is_scalar($scope['subject'])) {
            $request->attributes->set('_analytics_token_subject', trim((string) $scope['subject']));
        }
    }

    private function isSafePublicReadRoute(Request $request, string $route): bool
    {
        return $request->isMethodSafe() && in_array($route, self::PUBLIC_READ_ROUTES, true);
    }

    /**
     * @param array<string,mixed> $scope
     */
    private function isRouteAllowed(string $route, array $scope): bool
    {
        if (($scope['admin'] ?? false) === true) {
            return true;
        }

        $routes = $scope['routes'] ?? null;
        if (!is_array($routes)) {
            return false;
        }

        return array_any($routes, fn ($allowedRoute) => is_scalar($allowedRoute) && trim((string) $allowedRoute) === $route);
    }

    /**
     * @param array<string,mixed> $scope
     */
    private function isVendorAllowed(Request $request, array $scope): bool
    {
        $scopeVendor = $scope['vendor'] ?? null;
        if (!is_scalar($scopeVendor) || '' === trim((string) $scopeVendor)) {
            return true;
        }

        $requestedVendor = $this->vendorResolver->resolve($request);
        if ('public' === $requestedVendor) {
            return true;
        }

        return hash_equals(trim((string) $scopeVendor), (string) $requestedVendor);
    }

    private function extractToken(Request $request): ?string
    {
        $headerToken = trim((string) $request->headers->get('X-Analytics-Token', ''));
        if ('' !== $headerToken) {
            return $headerToken;
        }

        $authorization = trim((string) $request->headers->get('Authorization', ''));
        if (1 === preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
            $bearerToken = trim($matches[1]);
            if ('' !== $bearerToken) {
                return $bearerToken;
            }
        }

        return null;
    }

    private function reject(string $error, string $errorCode, int $status, string $route, float $startedAt): Response
    {
        $this->logger->warning('Analytics request authorization rejected.', [
            'route' => $route,
            'status' => $status,
            'error_code' => $errorCode,
        ]);

        return $this->errors->create($route, $error, $errorCode, $status, $startedAt);
    }

    /**
     * @param array<array-key, mixed> $scope
     *
     * @return array<string, mixed>
     */
    private function normalizeAssociativeArray(array $scope): array
    {
        $normalized = [];
        foreach ($scope as $key => $value) {
            if (!is_string($key)) {
                throw new \InvalidArgumentException('Analytics authorization scope is invalid.');
            }

            $normalized[$key] = $value;
        }

        return $normalized;
    }
}
