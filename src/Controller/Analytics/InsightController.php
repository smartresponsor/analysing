<?php

/*
 * Owner: Marketing America Corp
 * Author: Oleksandr Tishchenko <dev@highhopesamerica.com>
 */

declare(strict_types=1);

namespace App\Controller\Analytics;

use App\ControllerInterface\Analytics\InsightControllerInterface;
use App\DomainInterface\Analytics\InsightInterface;
use App\Service\Http\AnalyticsErrorResponseFactory;
use App\Service\Http\AnalyticsSuccessResponseFactory;
use App\Service\Http\JsonRequestBodyDecoder;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class InsightController implements InsightControllerInterface
{
    private const string COMPONENT = 'analytics';

    public function __construct(
        private readonly InsightInterface $domain,
        private readonly LoggerInterface $logger,
        private readonly AnalyticsSuccessResponseFactory $successResponses,
        private readonly AnalyticsErrorResponseFactory $errorResponses,
        private readonly ?JsonRequestBodyDecoder $jsonDecoder = null,
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

            $this->logger->info('Insight domain operation completed.', [
                'operation' => $operation,
                'component' => self::COMPONENT,
                'result_count' => is_countable($result) ? count($result) : null,
                'duration_ms' => $this->durationMs($startedAt),
            ]);

            return $this->successResponses->create($operation, $result, $startedAt);
        } catch (\InvalidArgumentException $exception) {
            $this->logger->warning('Insight request is invalid.', [
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
            $this->logger->error('Insight domain failed.', [
                'operation' => $operation,
                'component' => self::COMPONENT,
                'duration_ms' => $this->durationMs($startedAt),
                'exception' => $exception,
            ]);

            return $this->errorResponses->create(
                $operation,
                'Insight data unavailable.',
                'analytics.insight.unavailable',
                Response::HTTP_SERVICE_UNAVAILABLE,
                $startedAt,
                true,
            );
        } catch (\Throwable $exception) {
            $this->logger->error('Insight domain failed unexpectedly.', [
                'operation' => $operation,
                'component' => self::COMPONENT,
                'duration_ms' => $this->durationMs($startedAt),
                'exception' => $exception,
            ]);

            return $this->errorResponses->create(
                $operation,
                'Insight data unavailable.',
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
        return ($this->jsonDecoder ?? new JsonRequestBodyDecoder())->decode($request);
    }

    private function durationMs(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }
}
