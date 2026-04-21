<?php

/*
 * Owner: Marketing America Corp
 * Author: Oleksandr Tishchenko <dev@highhopesamerica.com>
 */

declare(strict_types=1);

namespace App\Analysing\Controller\Analytics;

use App\Analysing\ControllerInterface\Analytics\IngestControllerInterface;
use App\Analysing\DomainInterface\Analytics\ClickhouseClientInterface;
use App\Analysing\Service\Http\AnalyticsErrorResponseFactory;
use App\Analysing\Service\Http\AnalyticsSuccessResponseFactory;
use App\Analysing\Service\Http\JsonRequestBodyDecoder;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class IngestController implements IngestControllerInterface
{
    private const string COMPONENT = 'analytics';
    private const int MAX_BATCH_SIZE = 1000;
    private const int MAX_STRING_LENGTH = 255;

    public function __construct(
        private readonly ClickhouseClientInterface $client,
        private readonly LoggerInterface $logger,
        private readonly AnalyticsSuccessResponseFactory $successResponses,
        private readonly AnalyticsErrorResponseFactory $errorResponses,
        private readonly ?JsonRequestBodyDecoder $jsonDecoder = null,
    ) {
    }

    public function ingestRudder(Request $request): JsonResponse
    {
        return $this->runIngestOperation('rudder', $request);
    }

    public function ingestSegment(Request $request): JsonResponse
    {
        return $this->runIngestOperation('segment', $request);
    }

    private function runIngestOperation(string $source, Request $request): JsonResponse
    {
        $startedAt = microtime(true);

        try {
            $result = $this->ingestCommon($source, $request);

            $this->logger->info('Analytics ingest completed.', [
                'source' => $source,
                'component' => self::COMPONENT,
                'accepted' => $result['accepted'],
                'skipped' => $result['skipped'],
                'duration_ms' => $this->durationMs($startedAt),
            ]);

            return $this->successResponses->create($source, array_merge(['source' => $source], $result), $startedAt);
        } catch (\InvalidArgumentException $exception) {
            $this->logger->warning('Analytics ingest request rejected.', [
                'source' => $source,
                'component' => self::COMPONENT,
                'duration_ms' => $this->durationMs($startedAt),
                'exception' => $exception,
            ]);

            return $this->errorResponses->create(
                $source,
                'Invalid analytics ingest request.',
                'analytics.ingest.invalid_request',
                Response::HTTP_BAD_REQUEST,
                $startedAt,
                false,
                ['source' => $source],
            );
        } catch (\RuntimeException $exception) {
            $this->logger->error('Analytics ingest failed.', [
                'source' => $source,
                'component' => self::COMPONENT,
                'duration_ms' => $this->durationMs($startedAt),
                'exception' => $exception,
            ]);

            return $this->errorResponses->create(
                $source,
                'Analytics ingest unavailable.',
                'analytics.ingest.unavailable',
                Response::HTTP_SERVICE_UNAVAILABLE,
                $startedAt,
                true,
                ['source' => $source],
            );
        }
    }

    /**
     * @return array{accepted:int,skipped:int}
     */
    private function ingestCommon(string $source, Request $request): array
    {
        $payload = $this->decodeBody($request);
        $tenant = $this->normalizeIdentifier($request->headers->get('X-SR-TENANT') ?? 'unknown', 'tenant header');
        $batch = isset($payload['batch']) && is_array($payload['batch']) ? array_values($payload['batch']) : [$payload];
        if (count($batch) > self::MAX_BATCH_SIZE) {
            throw new \InvalidArgumentException('Ingest batch contains too many items.');
        }

        $rows = [];
        $skipped = 0;

        foreach ($batch as $item) {
            if (!is_array($item)) {
                ++$skipped;
                continue;
            }

            $type = $this->normalizeIdentifier((string) ($item['type'] ?? 'track'), 'event type');
            $event = $this->normalizeIdentifier((string) ($item['event'] ?? ('page' === $type ? 'page' : $type)), 'event name');
            $userId = $this->normalizeIdentifier((string) ($item['userId'] ?? $item['anonymousId'] ?? 'anon'), 'user id');
            $context = isset($item['context']) && is_array($item['context']) ? $item['context'] : [];
            $sessionId = $this->normalizeOptionalSessionIdentifier($context['sessionId'] ?? null);
            $timestamp = $this->normalizeTimestamp($item['timestamp'] ?? null);

            $rows[] = [
                'event_name' => $event,
                'event_type' => $type,
                'user_id' => $userId,
                'session_id' => $sessionId,
                'tenant_id' => $tenant,
                'source' => $source,
                'timestamp' => $timestamp,
                'properties' => $item['properties'] ?? new \stdClass(),
            ];
        }

        if ([] === $rows) {
            return ['accepted' => 0, 'skipped' => $skipped];
        }

        $this->client->insertJsonEachRow('event', $rows);

        return ['accepted' => count($rows), 'skipped' => $skipped];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeBody(Request $request): array
    {
        return ($this->jsonDecoder ?? new JsonRequestBodyDecoder())->decode($request);
    }

    private function normalizeIdentifier(string $value, string $field): string
    {
        $normalized = trim($value);
        if ('' === $normalized) {
            throw new \InvalidArgumentException(sprintf('%s must be a non-empty string.', ucfirst($field)));
        }

        if (strlen($normalized) > self::MAX_STRING_LENGTH) {
            throw new \InvalidArgumentException(sprintf('%s is too long.', ucfirst($field)));
        }

        return $normalized;
    }

    private function normalizeOptionalSessionIdentifier(mixed $value): string
    {
        if (null === $value || '' === $value) {
            return '';
        }

        if (!is_scalar($value)) {
            throw new \InvalidArgumentException('Session id must be a scalar identifier.');
        }

        return $this->normalizeIdentifier((string) $value, 'session id');
    }

    private function normalizeTimestamp(mixed $value): string
    {
        if (null === $value || '' === $value) {
            return (new \DateTimeImmutable())->format(DATE_ATOM);
        }

        if (!is_string($value)) {
            throw new \InvalidArgumentException('timestamp must be a valid date/time string.');
        }

        try {
            return (new \DateTimeImmutable($value))->format(DATE_ATOM);
        } catch (\Exception $exception) {
            throw new \InvalidArgumentException('timestamp must be a valid date/time string.', 0, $exception);
        }
    }

    private function durationMs(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }
}
