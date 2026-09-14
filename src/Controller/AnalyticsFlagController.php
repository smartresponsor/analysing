<?php

declare(strict_types=1);

namespace App\Analysing\Controller;

use App\Analysing\Factory\Http\AnalyticsErrorResponseFactory;
use App\Analysing\Factory\Http\AnalyticsSuccessResponseFactory;
use App\Analysing\Service\Http\AnalyticsJsonRequestBodyDecoder;
use App\Analysing\ServiceInterface\AnalyticsFlagInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class AnalyticsFlagController implements AnalyticsFlagControllerInterface
{
    private const string COMPONENT = 'analytics';

    public function __construct(
        private readonly AnalyticsFlagInterface $domain,
        private readonly LoggerInterface $logger,
        private readonly AnalyticsSuccessResponseFactory $successResponses,
        private readonly AnalyticsErrorResponseFactory $errorResponses,
        private readonly ?AnalyticsJsonRequestBodyDecoder $jsonDecoder = null,
    ) {
    }

    public function evaluate(Request $request): JsonResponse
    {
        $startedAt = microtime(true);

        try {
            $result = $this->domain->evaluate($this->decodeBody($request));

            $this->logger->info('AnalyticsFlag evaluation completed.', [
                'operation' => 'evaluate',
                'component' => self::COMPONENT,
                'enabled' => $result['enabled'],
                'duration_ms' => $this->durationMs($startedAt),
            ]);

            return $this->successResponses->create('evaluate', $result, $startedAt);
        } catch (\InvalidArgumentException $exception) {
            $this->logger->warning('AnalyticsFlag evaluation request is invalid.', [
                'component' => self::COMPONENT,
                'duration_ms' => $this->durationMs($startedAt),
                'exception' => $exception,
            ]);

            return $this->errorResponses->create(
                'evaluate',
                'Invalid flag request.',
                'analytics.flag.invalid_request',
                Response::HTTP_BAD_REQUEST,
                $startedAt,
            );
        } catch (\RuntimeException $exception) {
            $this->logger->error('AnalyticsFlag evaluation failed.', [
                'component' => self::COMPONENT,
                'duration_ms' => $this->durationMs($startedAt),
                'exception' => $exception,
            ]);

            return $this->errorResponses->create(
                'evaluate',
                'AnalyticsFlag evaluation unavailable.',
                'analytics.flag.unavailable',
                Response::HTTP_SERVICE_UNAVAILABLE,
                $startedAt,
                true,
            );
        } catch (\Throwable $exception) {
            $this->logger->error('AnalyticsFlag evaluation failed unexpectedly.', [
                'operation' => 'evaluate',
                'component' => self::COMPONENT,
                'duration_ms' => $this->durationMs($startedAt),
                'exception' => $exception,
            ]);

            return $this->errorResponses->create(
                'evaluate',
                'AnalyticsFlag evaluation unavailable.',
                'analytics.flag.failed',
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
