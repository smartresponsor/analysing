<?php

/*
 * Owner: Marketing America Corp
 * Author: Oleksandr Tishchenko <dev@highhopesamerica.com>
 */

declare(strict_types=1);

namespace App\Analysing\Controller;

use App\Analysing\Factory\Http\AnalyticsErrorResponseFactory;
use App\Analysing\Factory\Http\AnalyticsSuccessResponseFactory;
use App\Analysing\Service\Http\AnalyticsJsonRequestBodyDecoder;
use App\Analysing\ServiceInterface\AnalyticsInsightInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class AnalyticsInsightController implements AnalyticsInsightControllerInterface
{
    private const string COMPONENT = 'analytics';

    public function __construct(
        private readonly AnalyticsInsightInterface $domain,
        private readonly LoggerInterface $logger,
        private readonly AnalyticsSuccessResponseFactory $successResponses,
        private readonly AnalyticsErrorResponseFactory $errorResponses,
        private readonly ?AnalyticsJsonRequestBodyDecoder $jsonDecoder = null,
    ) {
    }

    public function anomaly(Request $request): JsonResponse
    {
        return $this->runDomainOperation('anomaly', fn (): array => $this->domain->detectAnomaly($this->decodeBody($request)));
    }

    public function metricTree(Request $request): JsonResponse
    {
        return $this->runDomainOperation('metric_tree', fn (): array => $this->domain->computeMetricTree($this->decodeBody($request)));
    }

    private function runDomainOperation(string $operation, callable $callback): JsonResponse
    {
        $startedAt = microtime(true);

        try {
            $result = $callback();

            $this->logger->info('AnalyticsInsight domain operation completed.', [
                'operation' => $operation,
                'component' => self::COMPONENT,
                'result_count' => is_countable($result) ? count($result) : null,
                'duration_ms' => $this->durationMs($startedAt),
            ]);

            return $this->successResponses->create($operation, $result, $startedAt);
        } catch (\InvalidArgumentException $exception) {
            $this->logger->warning('AnalyticsInsight request is invalid.', [
                'operation' => $operation,
                'component' => self::COMPONENT,
                'duration_ms' => $this->durationMs($startedAt),
                'exception' => $exception,
            ]);

            return $this->errorResponses->create(
                $operation,
                'Invalid insight request.',
                'analytics.insight.invalid_request',
                Response::HTTP_BAD_REQUEST,
                $startedAt,
            );
        } catch (\RuntimeException $exception) {
            $this->logger->error('AnalyticsInsight domain failed.', [
                'operation' => $operation,
                'component' => self::COMPONENT,
                'duration_ms' => $this->durationMs($startedAt),
                'exception' => $exception,
            ]);

            return $this->errorResponses->create(
                $operation,
                'AnalyticsInsight data unavailable.',
                'analytics.insight.unavailable',
                Response::HTTP_SERVICE_UNAVAILABLE,
                $startedAt,
                true,
            );
        } catch (\Throwable $exception) {
            $this->logger->error('AnalyticsInsight domain failed unexpectedly.', [
                'operation' => $operation,
                'component' => self::COMPONENT,
                'duration_ms' => $this->durationMs($startedAt),
                'exception' => $exception,
            ]);

            return $this->errorResponses->create(
                $operation,
                'AnalyticsInsight data unavailable.',
                'analytics.insight.failed',
                Response::HTTP_INTERNAL_SERVER_ERROR,
                $startedAt,
                true,
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeBody(Request $request): array
    {
        return ($this->jsonDecoder ?? new AnalyticsJsonRequestBodyDecoder())->decode($request);
    }

    private function durationMs(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }
}
