<?php

/*
 * Owner: Marketing America Corp
 * Author: Oleksandr Tishchenko <dev@highhopesamerica.com>
 */

declare(strict_types=1);

namespace App\Controller\Analytics;

use App\ControllerInterface\Analytics\InsightControllerInterface;
use App\DomainInterface\Analytics\InsightInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class InsightController implements InsightControllerInterface
{
    private const COMPONENT = 'analytics';
    private const MAX_JSON_BYTES = 1048576;

    public function __construct(
        private readonly InsightInterface $domain,
        private readonly LoggerInterface $logger,
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

            return new JsonResponse($result);
        } catch (\InvalidArgumentException $exception) {
            $this->logger->warning('Insight request is invalid.', [
                'operation' => $operation,
                'component' => self::COMPONENT,
                'duration_ms' => $this->durationMs($startedAt),
                'exception' => $exception,
            ]);

            return new JsonResponse([
                'error' => 'Invalid insight request.',
                'operation' => $operation,
                'component' => self::COMPONENT,
                'time' => (new \DateTimeImmutable())->format(DATE_ATOM),
            ], Response::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $exception) {
            $this->logger->error('Insight domain failed.', [
                'operation' => $operation,
                'component' => self::COMPONENT,
                'duration_ms' => $this->durationMs($startedAt),
                'exception' => $exception,
            ]);

            return new JsonResponse([
                'error' => 'Insight data unavailable.',
                'operation' => $operation,
                'component' => self::COMPONENT,
                'time' => (new \DateTimeImmutable())->format(DATE_ATOM),
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }
    }

    private function decodeBody(Request $request): array
    {
        $content = trim($request->getContent());
        if ('' === $content) {
            return [];
        }

        if (strlen($content) > self::MAX_JSON_BYTES) {
            throw new \InvalidArgumentException('JSON payload is too large.');
        }

        try {
            $payload = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \InvalidArgumentException('Invalid JSON payload.', 0, $exception);
        }

        if (!is_array($payload)) {
            throw new \InvalidArgumentException('JSON payload must decode to an object or array.');
        }

        return $payload;
    }

    private function durationMs(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }
}
