<?php

/*
 * Marketing America Corp. Oleksandr Tishchenko
 * Author: Oleksandr Tishchenko <dev@highhopesamerica.com>
 */

declare(strict_types=1);

namespace App\Controller\Analytics;

use App\ControllerInterface\Analytics\AnalyticsControllerInterface;
use App\DomainInterface\Analytics\AnalyticsInterface;
use App\Service\Http\AnalyticsErrorResponseFactory;
use App\Service\Http\AnalyticsSuccessResponseFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class AnalyticsController implements AnalyticsControllerInterface
{
    private const COMPONENT = 'analytics';
    private const MAX_JSON_BYTES = 1048576;

    public function __construct(
        private readonly AnalyticsInterface $domain,
        private readonly LoggerInterface $logger,
        private readonly AnalyticsSuccessResponseFactory $successResponses,
        private readonly AnalyticsErrorResponseFactory $errorResponses,
    ) {
    }

    public function status(): JsonResponse
    {
        $startedAt = microtime(true);
        $payload = ['status' => 'ok'];

        $this->logger->info('Analytics status endpoint completed.', [
            'component' => self::COMPONENT,
            'status' => 'ok',
            'duration_ms' => $this->durationMs($startedAt),
        ]);

        return $this->successResponses->create('status', $payload, $startedAt);
    }

    public function funnel(Request $request): JsonResponse
    {
        return $this->runDomainOperation('funnel', fn (): array => $this->domain->runFunnel($this->decodeBody($request)));
    }

    public function retention(Request $request): JsonResponse
    {
        return $this->runDomainOperation('retention', fn (): array => $this->domain->runRetention($this->decodeBody($request)));
    }

    public function cohort(Request $request): JsonResponse
    {
        return $this->runDomainOperation('cohort', fn (): array => $this->domain->runCohort($this->decodeBody($request)));
    }

    private function runDomainOperation(string $operation, callable $callback): JsonResponse
    {
        $startedAt = microtime(true);

        try {
            $result = $callback();

            $this->logger->info('Analytics domain operation completed.', [
                'operation' => $operation,
                'component' => self::COMPONENT,
                'result_count' => is_countable($result) ? count($result) : null,
                'duration_ms' => $this->durationMs($startedAt),
            ]);

            return $this->successResponses->create($operation, $result, $startedAt);
        } catch (\InvalidArgumentException $exception) {
            $this->logger->warning('Analytics domain rejected request.', [
                'operation' => $operation,
                'component' => self::COMPONENT,
                'duration_ms' => $this->durationMs($startedAt),
                'exception' => $exception,
            ]);

            return $this->errorResponses->create(
                $operation,
                'Invalid analytics request.',
                'analytics.request.invalid',
                Response::HTTP_BAD_REQUEST,
                $startedAt,
            );
        } catch (\RuntimeException $exception) {
            $this->logger->error('Analytics domain failed.', [
                'operation' => $operation,
                'component' => self::COMPONENT,
                'duration_ms' => $this->durationMs($startedAt),
                'exception' => $exception,
            ]);

            return $this->errorResponses->create(
                $operation,
                'Analytics data unavailable.',
                'analytics.request.unavailable',
                Response::HTTP_SERVICE_UNAVAILABLE,
                $startedAt,
                true,
            );
        }
    }

    /**
     * @return array<string,mixed>
     */
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
