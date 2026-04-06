<?php

declare(strict_types=1);

namespace App\Controller\Analytics;

use App\ControllerInterface\Analytics\FlagControllerInterface;
use App\DomainInterface\Analytics\FlagInterface;
use App\Service\Http\AnalyticsErrorResponseFactory;
use App\Service\Http\AnalyticsSuccessResponseFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class FlagController implements FlagControllerInterface
{
    private const COMPONENT = 'analytics';
    private const MAX_JSON_BYTES = 1048576;

    public function __construct(
        private readonly FlagInterface $domain,
        private readonly LoggerInterface $logger,
        private readonly AnalyticsSuccessResponseFactory $successResponses,
        private readonly AnalyticsErrorResponseFactory $errorResponses,
    ) {
    }

    public function evaluate(Request $request): JsonResponse
    {
        $startedAt = microtime(true);

        try {
            $result = $this->domain->evaluate($this->decodeBody($request));

            $this->logger->info('Flag evaluation completed.', [
                'operation' => 'evaluate',
                'component' => self::COMPONENT,
                'enabled' => $result['enabled'],
                'duration_ms' => $this->durationMs($startedAt),
            ]);

            return $this->successResponses->create('evaluate', $result, $startedAt);
        } catch (\InvalidArgumentException $exception) {
            $this->logger->warning('Flag evaluation request is invalid.', [
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
            $this->logger->error('Flag evaluation failed.', [
                'component' => self::COMPONENT,
                'duration_ms' => $this->durationMs($startedAt),
                'exception' => $exception,
            ]);

            return $this->errorResponses->create(
                'evaluate',
                'Flag evaluation unavailable.',
                'analytics.flag.unavailable',
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
