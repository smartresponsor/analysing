<?php

declare(strict_types=1);

namespace App\Service\Http;

use App\ServiceInterface\Http\AnalyticsSuccessResponseFactoryInterface;
use App\ServiceInterface\Http\RequestCorrelationIdProviderInterface;
use App\ServiceInterface\Http\TenantContextInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class AnalyticsSuccessResponseFactory implements AnalyticsSuccessResponseFactoryInterface
{
    private const string COMPONENT = 'analytics';

    public function __construct(
        private readonly RequestCorrelationIdProviderInterface $correlationIds,
        private readonly TenantContextInterface $tenantContext,
    ) {
    }

    public function create(string $operation, mixed $data, float $startedAt, int $status = Response::HTTP_OK): JsonResponse
    {
        $payload = [
            'ok' => true,
            'component' => self::COMPONENT,
            'time' => $this->currentTime(),
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

    private function currentTime(): string
    {
        return gmdate(DATE_ATOM);
    }
}
