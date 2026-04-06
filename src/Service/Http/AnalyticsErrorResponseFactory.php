<?php

declare(strict_types=1);

namespace App\Service\Http;

use App\ServiceInterface\Http\AnalyticsErrorResponseFactoryInterface;
use App\ServiceInterface\Http\RequestCorrelationIdProviderInterface;
use App\ServiceInterface\Http\TenantContextInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

final class AnalyticsErrorResponseFactory implements AnalyticsErrorResponseFactoryInterface
{
    private const COMPONENT = 'analytics';

    public function __construct(
        private readonly RequestCorrelationIdProviderInterface $correlationIds,
        private readonly TenantContextInterface $tenantContext,
    ) {
    }

    /**
     * @param array<string,mixed> $extra
     */
    public function create(
        string $operation,
        string $error,
        string $errorCode,
        int $status,
        float $startedAt,
        bool $retryable = false,
        array $extra = [],
    ): JsonResponse {
        $payload = [
            'ok' => false,
            'error' => $error,
            'error_code' => $errorCode,
            'status' => $status,
            'retryable' => $retryable,
            'component' => self::COMPONENT,
            'operation' => $operation,
            'time' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(DATE_ATOM),
            'duration_ms' => $this->durationMs($startedAt),
            'correlation_id' => $this->correlationIds->current(),
            'tenant' => $this->tenantContext->current(),
        ];

        return new JsonResponse(array_merge($payload, $extra), $status);
    }

    private function durationMs(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }
}
