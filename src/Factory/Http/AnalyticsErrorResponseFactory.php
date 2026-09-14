<?php

declare(strict_types=1);

namespace App\Analysing\Factory\Http;

use App\Analysing\FactoryInterface\Http\AnalyticsErrorResponseFactoryInterface;
use App\Analysing\ProviderInterface\Http\AnalyticsRequestCorrelationIdProviderInterface;
use App\Analysing\ServiceInterface\Http\AnalyticsVendorContextInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

final class AnalyticsErrorResponseFactory implements AnalyticsErrorResponseFactoryInterface
{
    private const string COMPONENT = 'analytics';

    public function __construct(
        private readonly AnalyticsRequestCorrelationIdProviderInterface $correlationIds,
        private readonly AnalyticsVendorContextInterface $vendorContext,
    ) {
    }

    /**
     * @param string              $operation
     * @param string              $error
     * @param string              $errorCode
     * @param int                 $status
     * @param float               $startedAt
     * @param bool                $retryable
     * @param array<string,mixed> $extra
     *
     * @return JsonResponse
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
            'time' => $this->currentTime(),
            'duration_ms' => $this->durationMs($startedAt),
            'correlation_id' => $this->correlationIds->current(),
            'vendor' => $this->vendorContext->current(),
        ];

        return new JsonResponse(array_merge($payload, $extra), $status);
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
