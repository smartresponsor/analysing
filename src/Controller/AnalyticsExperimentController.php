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
use App\Analysing\ServiceInterface\AnalyticsExperimentInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class AnalyticsExperimentController implements AnalyticsExperimentControllerInterface
{
    private const string COMPONENT = 'analytics';

    public function __construct(
        private readonly AnalyticsExperimentInterface $domain,
        private readonly LoggerInterface $logger,
        private readonly AnalyticsSuccessResponseFactory $successResponses,
        private readonly AnalyticsErrorResponseFactory $errorResponses,
        private readonly ?AnalyticsJsonRequestBodyDecoder $jsonDecoder = null,
    ) {
    }

    public function allocate(Request $request): JsonResponse
    {
        $startedAt = microtime(true);

        try {
            $result = $this->domain->assign($this->decodeBody($request));

            $this->logger->info('AnalyticsExperiment allocation completed.', [
                'operation' => 'allocate',
                'component' => self::COMPONENT,
                'has_variant' => array_key_exists('variant', $result),
                'duration_ms' => $this->durationMs($startedAt),
            ]);

            return $this->successResponses->create('allocate', $result, $startedAt);
        } catch (\InvalidArgumentException $exception) {
            $this->logger->warning('AnalyticsExperiment allocation request is invalid.', [
                'component' => self::COMPONENT,
                'duration_ms' => $this->durationMs($startedAt),
                'exception' => $exception,
            ]);

            return $this->errorResponses->create(
                'allocate',
                'Invalid experiment request.',
                'analytics.experiment.invalid_request',
                Response::HTTP_BAD_REQUEST,
                $startedAt,
            );
        } catch (\RuntimeException $exception) {
            $this->logger->error('AnalyticsExperiment allocation failed.', [
                'component' => self::COMPONENT,
                'duration_ms' => $this->durationMs($startedAt),
                'exception' => $exception,
            ]);

            return $this->errorResponses->create(
                'allocate',
                'AnalyticsExperiment allocation unavailable.',
                'analytics.experiment.unavailable',
                Response::HTTP_SERVICE_UNAVAILABLE,
                $startedAt,
                true,
            );
        } catch (\Throwable $exception) {
            $this->logger->error('AnalyticsExperiment allocation failed unexpectedly.', [
                'operation' => 'allocate',
                'component' => self::COMPONENT,
                'duration_ms' => $this->durationMs($startedAt),
                'exception' => $exception,
            ]);

            return $this->errorResponses->create(
                'allocate',
                'AnalyticsExperiment allocation unavailable.',
                'analytics.experiment.failed',
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
