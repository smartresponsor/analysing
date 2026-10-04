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

    /**
     * @param callable(): array<mixed> $callback
     */
    private function runDomainOperation(string $operation, callable $callback): JsonResponse
    {
        $startedAt = microtime(true);

        try {
            return $this->completeDomainOperation($operation, $callback(), $startedAt);
        } catch (\InvalidArgumentException $exception) {
            return $this->rejectDomainOperation($operation, $exception, $startedAt);
        } catch (\RuntimeException $exception) {
            return $this->failUnavailableDomainOperation($operation, $exception, $startedAt);
        } catch (\Throwable $exception) {
            return $this->failUnexpectedDomainOperation($operation, $exception, $startedAt);
        }
    }

    /**
     * @param array<mixed> $result
     */
    private function completeDomainOperation(string $operation, array $result, float $startedAt): JsonResponse
    {
        $this->logger->info('AnalyticsInsight domain operation completed.', [
            'operation' => $operation,
            'component' => self::COMPONENT,
            'result_count' => count($result),
            'duration_ms' => $this->durationMs($startedAt),
        ]);

        return $this->successResponses->create($operation, $result, $startedAt);
    }

    private function rejectDomainOperation(string $operation, \InvalidArgumentException $exception, float $startedAt): JsonResponse
    {
        $this->logger->warning('AnalyticsInsight request is invalid.', [
            'operation' => $operation,
            'component' => self::COMPONENT,
            'duration_ms' => $this->durationMs($startedAt),
            'exception' => $exception,
        ]);

        return $this->errorResponses->create($operation, 'Invalid insight request.', 'analytics.insight.invalid_request', Response::HTTP_BAD_REQUEST, $startedAt);
    }

    private function failUnavailableDomainOperation(string $operation, \RuntimeException $exception, float $startedAt): JsonResponse
    {
        $this->logger->error('AnalyticsInsight domain failed.', [
            'operation' => $operation,
            'component' => self::COMPONENT,
            'duration_ms' => $this->durationMs($startedAt),
            'exception' => $exception,
        ]);

        return $this->errorResponses->create($operation, 'AnalyticsInsight data unavailable.', 'analytics.insight.unavailable', Response::HTTP_SERVICE_UNAVAILABLE, $startedAt, true);
    }

    private function failUnexpectedDomainOperation(string $operation, \Throwable $exception, float $startedAt): JsonResponse
    {
        $this->logger->error('AnalyticsInsight domain failed unexpectedly.', [
            'operation' => $operation,
            'component' => self::COMPONENT,
            'duration_ms' => $this->durationMs($startedAt),
            'exception' => $exception,
        ]);

        return $this->errorResponses->create($operation, 'AnalyticsInsight data unavailable.', 'analytics.insight.failed', Response::HTTP_INTERNAL_SERVER_ERROR, $startedAt, true);
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
