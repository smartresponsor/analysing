<?php

declare(strict_types=1);

namespace App\Analysing\Factory\Http;

use App\Analysing\FactoryInterface\Http\AnalyticsSuccessResponseFactoryInterface;
use App\Analysing\ProviderInterface\Http\AnalyticsRequestCorrelationIdProviderInterface;
use App\Analysing\ServiceInterface\Http\AnalyticsVendorContextInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class AnalyticsSuccessResponseFactory implements AnalyticsSuccessResponseFactoryInterface
{
    private const string COMPONENT = 'analytics';

    public function __construct(
        private readonly AnalyticsRequestCorrelationIdProviderInterface $correlationIds,
        private readonly AnalyticsVendorContextInterface $vendorContext,
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
            'vendor' => $this->vendorContext->current(),
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
