<?php

/*
 * Marketing America Corp. Oleksandr Tishchenko
 * Author: Oleksandr Tishchenko <dev@highhopesamerica.com>
 */

declare(strict_types=1);

namespace App\Controller\Analytics;

use App\ControllerInterface\Analytics\ApiControllerInterface;
use App\Service\Http\AnalyticsErrorResponseFactory;
use App\Service\Http\AnalyticsSuccessResponseFactory;
use App\ServiceInterface\Analytics\KpiRegistryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

final class ApiController implements ApiControllerInterface
{
    private const string COMPONENT = 'analytics';
    private const string OPERATION = 'metrics';

    public function __construct(
        private readonly KpiRegistryInterface $registry,
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
                $this->logger->error('Analytics API metrics endpoint detected an empty KPI catalog.', [
                    'component' => self::COMPONENT,
                    'operation' => self::OPERATION,
                    'duration_ms' => $this->durationMs($startedAt),
                ]);

                return $this->errorResponses->create(
                    self::OPERATION,
                    'Metrics catalog unavailable.',
                    'analytics.metrics.unavailable',
                    JsonResponse::HTTP_SERVICE_UNAVAILABLE,
                    $startedAt,
                    true,
                );
            }

            $metrics = [];
            foreach ($catalog as $key => $label) {
                $metrics[] = [
                    'key' => $key,
                    'label' => $label,
                ];
            }

            try {
                $catalogChecksum = hash('sha256', json_encode($metrics, JSON_THROW_ON_ERROR));
            } catch (\JsonException $exception) {
                throw new \RuntimeException('Unable to encode metrics catalog checksum.', 0, $exception);
            }

            $response = [
                'metric_count' => count($metrics),
                'catalog_checksum' => $catalogChecksum,
                'metrics' => $metrics,
            ];

            $this->logger->info('Analytics API metrics endpoint completed.', [
                'component' => self::COMPONENT,
                'operation' => self::OPERATION,
                'metric_count' => $response['metric_count'],
                'catalog_checksum' => $catalogChecksum,
                'duration_ms' => $this->durationMs($startedAt),
            ]);

            return $this->successResponses->create(self::OPERATION, $response, $startedAt);
        } catch (\InvalidArgumentException|\RuntimeException $exception) {
            $this->logger->error('Analytics API metrics endpoint failed.', [
                'component' => self::COMPONENT,
                'operation' => self::OPERATION,
                'duration_ms' => $this->durationMs($startedAt),
                'exception' => $exception,
            ]);

            return $this->errorResponses->create(
                self::OPERATION,
                'Metrics catalog unavailable.',
                'analytics.metrics.unavailable',
                JsonResponse::HTTP_SERVICE_UNAVAILABLE,
                $startedAt,
                true,
            );
        }
    }

    private function durationMs(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }
}
