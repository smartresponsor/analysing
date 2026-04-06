<?php

/*
 * Owner: Marketing America Corp
 * Author: Oleksandr Tishchenko <dev@highhopesamerica.com>
 */

declare(strict_types=1);

namespace App\Controller\Analytics;

use App\ControllerInterface\Analytics\ExperimentControllerInterface;
use App\DomainInterface\Analytics\ExperimentInterface;
use App\Service\Http\AnalyticsErrorResponseFactory;
use App\Service\Http\AnalyticsSuccessResponseFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ExperimentController implements ExperimentControllerInterface
{
    private const COMPONENT = 'analytics';
    private const MAX_JSON_BYTES = 1048576;

    public function __construct(
        private readonly ExperimentInterface $domain,
        private readonly LoggerInterface $logger,
        private readonly AnalyticsSuccessResponseFactory $successResponses,
        private readonly AnalyticsErrorResponseFactory $errorResponses,
    ) {
    }

    public function allocate(Request $request): JsonResponse
    {
        $startedAt = microtime(true);

        try {
            $result = $this->domain->assign($this->decodeBody($request));

            $this->logger->info('Experiment allocation completed.', [
                'operation' => 'allocate',
                'component' => self::COMPONENT,
                'has_variant' => array_key_exists('variant', $result),
                'duration_ms' => $this->durationMs($startedAt),
            ]);

            return $this->successResponses->create('allocate', $result, $startedAt);
        } catch (\InvalidArgumentException $exception) {
            $this->logger->warning('Experiment allocation request is invalid.', [
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
            $this->logger->error('Experiment allocation failed.', [
                'component' => self::COMPONENT,
                'duration_ms' => $this->durationMs($startedAt),
                'exception' => $exception,
            ]);

            return $this->errorResponses->create(
                'allocate',
                'Experiment allocation unavailable.',
                'analytics.experiment.unavailable',
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
