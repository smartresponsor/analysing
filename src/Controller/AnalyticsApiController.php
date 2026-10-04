<?php

/*
 * Marketing America Corp. Oleksandr Tishchenko
 * Author: Oleksandr Tishchenko <dev@highhopesamerica.com>
 */

declare(strict_types=1);

namespace App\Analysing\Controller;

use App\Analysing\Factory\Http\AnalyticsErrorResponseFactory;
use App\Analysing\Factory\Http\AnalyticsSuccessResponseFactory;
use App\Analysing\ServiceInterface\AnalyticsKpiRegistryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class AnalyticsApiController implements AnalyticsApiControllerInterface
{
    private const string COMPONENT = 'analytics';
    private const string OPERATION = 'metrics';

    public function __construct(
        private readonly AnalyticsKpiRegistryInterface $registry,
        private readonly LoggerInterface $logger,
        private readonly AnalyticsSuccessResponseFactory $successResponses,
        private readonly AnalyticsErrorResponseFactory $errorResponses,
    ) {
    }

    public function metrics(): JsonResponse
    {
        $startedAt = microtime(true);

        try {
            $catalog = $this->registry->list();
            if ([] === $catalog) {
                return $this->catalogUnavailable($startedAt, 'Analytics API metrics endpoint detected an empty KPI catalog.');
            }

            $metrics = $this->buildMetrics($catalog);
            $catalogChecksum = $this->catalogChecksum($metrics);
            $response = [
                'metric_count' => count($metrics),
                'catalog_checksum' => $catalogChecksum,
                'metrics' => $metrics,
            ];

            $this->logSuccess($response['metric_count'], $catalogChecksum, $startedAt);

            return $this->successResponses->create(self::OPERATION, $response, $startedAt);
        } catch (\InvalidArgumentException|\RuntimeException $exception) {
            return $this->catalogUnavailable($startedAt, 'Analytics API metrics endpoint failed.', $exception);
        }
    }

    /**
     * @param array<string, string> $catalog
     *
     * @return list<array{key:string,label:string}>
     */
    private function buildMetrics(array $catalog): array
    {
        $metrics = [];
        foreach ($catalog as $key => $label) {
            $metrics[] = ['key' => $key, 'label' => $label];
        }

        return $metrics;
    }

    /**
     * @param list<array{key:string,label:string}> $metrics
     */
    private function catalogChecksum(array $metrics): string
    {
        try {
            return hash('sha256', json_encode($metrics, JSON_THROW_ON_ERROR));
        } catch (\JsonException $exception) {
            throw new \RuntimeException('Unable to encode metrics catalog checksum.', 0, $exception);
        }
    }

    private function logSuccess(int $metricCount, string $catalogChecksum, float $startedAt): void
    {
        $this->logger->info('Analytics API metrics endpoint completed.', [
            'component' => self::COMPONENT,
            'operation' => self::OPERATION,
            'metric_count' => $metricCount,
            'catalog_checksum' => $catalogChecksum,
            'duration_ms' => $this->durationMs($startedAt),
        ]);
    }

    private function catalogUnavailable(float $startedAt, string $message, ?\Throwable $exception = null): JsonResponse
    {
        $context = [
            'component' => self::COMPONENT,
            'operation' => self::OPERATION,
            'duration_ms' => $this->durationMs($startedAt),
        ];
        if (null !== $exception) {
            $context['exception'] = $exception;
        }
        $this->logger->error($message, $context);

        return $this->errorResponses->create(
            self::OPERATION,
            'Metrics catalog unavailable.',
            'analytics.metrics.unavailable',
            Response::HTTP_SERVICE_UNAVAILABLE,
            $startedAt,
            true,
        );
    }

    private function durationMs(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }
}
