<?php

declare(strict_types=1);

namespace App\Service\Http;

use App\ServiceInterface\Http\AnalyticsSuccessResponseFactoryInterface;
use App\ServiceInterface\Http\RequestCorrelationIdProviderInterface;
use App\ServiceInterface\Http\TenantContextInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

final class AnalyticsSuccessResponseFactory implements AnalyticsSuccessResponseFactoryInterface
{
    private const string COMPONENT = 'analytics';

    public function __construct(
        private readonly RequestCorrelationIdProviderInterface $correlationIds,
        private readonly TenantContextInterface $tenantContext,
    ) {
    }

    public function create(string $operation, mixed $data, float $startedAt, int $status = JsonResponse::HTTP_OK): JsonResponse
    {
        $payload = [
            'ok' => true,
            'component' => self::COMPONENT,
            'time' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(DATE_ATOM),
            'data' => $data,
            'operation' => $operation,
            'duration_ms' => $this->durationMs($startedAt),
            'correlation_id' => $this->correlationIds->current(),
            'tenant' => $this->tenantContext->current(),
        ];

        return new JsonResponse($payload, $status);
    }

    private function durationMs(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }
}
