<?php

/*
 * Marketing America Corp. Oleksandr Tishchenko
 * Author: Oleksandr Tishchenko <dev@highhopesamerica.com>
 */

declare(strict_types=1);

namespace App\Controller\Analytics;

use App\ControllerInterface\Analytics\HealthControllerInterface;
use App\Service\Http\AnalyticsErrorResponseFactory;
use App\Service\Http\AnalyticsSuccessResponseFactory;
use App\ServiceInterface\Analytics\HealthServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class HealthController implements HealthControllerInterface
{
    private const string COMPONENT = 'analytics';

    public function __construct(
        private readonly HealthServiceInterface $svc,
        private readonly LoggerInterface $logger,
        private readonly AnalyticsSuccessResponseFactory $successResponses,
        private readonly AnalyticsErrorResponseFactory $errorResponses,
    ) {
    }

    public function ping(): JsonResponse
    {
        $startedAt = microtime(true);

        try {
            $payload = $this->svc->status();
            $this->logger->info('Analytics health endpoint completed.', [
                'component' => self::COMPONENT,
                'ok' => $payload['ok'],
                'duration_ms' => $this->durationMs($startedAt),
            ]);

            return $this->successResponses->create('health', $payload, $startedAt);
        } catch (\RuntimeException $exception) {
            $this->logger->error('Analytics health endpoint failed.', [
                'component' => self::COMPONENT,
                'duration_ms' => $this->durationMs($startedAt),
                'exception' => $exception,
            ]);

            return $this->errorResponses->create(
                'health',
                'Health data unavailable.',
                'analytics.health.unavailable',
                Response::HTTP_SERVICE_UNAVAILABLE,
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
