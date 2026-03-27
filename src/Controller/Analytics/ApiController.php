<?php

/*
 * Marketing America Corp. Oleksandr Tishchenko
 * Author: Oleksandr Tishchenko <dev@highhopesamerica.com>
 */

declare(strict_types=1);

namespace App\Controller\Analytics;

use App\ControllerInterface\Analytics\ApiControllerInterface;
use App\ServiceInterface\Analytics\KpiRegistryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

final class ApiController implements ApiControllerInterface
{
    private const COMPONENT = 'analytics';
    private const OPERATION = 'metrics';

    public function __construct(
        private readonly KpiRegistryInterface $registry,
        private readonly LoggerInterface $logger,
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

                return new JsonResponse([
                    'ok' => false,
                    'component' => self::COMPONENT,
                    'operation' => self::OPERATION,
                    'error' => 'Metrics catalog unavailable.',
                    'time' => (new \DateTimeImmutable())->format(DATE_ATOM),
                ], JsonResponse::HTTP_SERVICE_UNAVAILABLE);
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
                'ok' => true,
                'component' => self::COMPONENT,
                'operation' => self::OPERATION,
                'time' => (new \DateTimeImmutable())->format(DATE_ATOM),
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

            return new JsonResponse($response);
        } catch (\InvalidArgumentException|\RuntimeException $exception) {
            $this->logger->error('Analytics API metrics endpoint failed.', [
                'component' => self::COMPONENT,
                'operation' => self::OPERATION,
                'duration_ms' => $this->durationMs($startedAt),
                'exception' => $exception,
            ]);

            return new JsonResponse([
                'ok' => false,
                'component' => self::COMPONENT,
                'operation' => self::OPERATION,
                'error' => 'Metrics catalog unavailable.',
                'time' => (new \DateTimeImmutable())->format(DATE_ATOM),
            ], JsonResponse::HTTP_SERVICE_UNAVAILABLE);
        }
    }

    private function durationMs(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }
}
