<?php

/*
 * Owner: Marketing America Corp
 * Author: Oleksandr Tishchenko <dev@highhopesamerica.com>
 */

declare(strict_types=1);

namespace App\Controller\Analytics;

use App\ControllerInterface\Analytics\IngestControllerInterface;
use App\DomainInterface\Analytics\ClickhouseClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class IngestController implements IngestControllerInterface
{
    private const COMPONENT = 'analytics';
    private const MAX_JSON_BYTES = 1048576;
    private const MAX_BATCH_SIZE = 1000;
    private const MAX_STRING_LENGTH = 255;

    public function __construct(
        private readonly ClickhouseClientInterface $client,
        private readonly LoggerInterface $logger,
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
                'accepted' => $result['accepted'] ?? null,
                'skipped' => $result['skipped'] ?? null,
                'duration_ms' => $this->durationMs($startedAt),
            ]);

            return new JsonResponse($result);
        } catch (\InvalidArgumentException $exception) {
            $this->logger->warning('Analytics ingest request rejected.', [
                'source' => $source,
                'component' => self::COMPONENT,
                'duration_ms' => $this->durationMs($startedAt),
                'exception' => $exception,
            ]);

            return new JsonResponse([
                'error' => 'Invalid analytics ingest request.',
                'source' => $source,
                'component' => self::COMPONENT,
                'time' => (new \DateTimeImmutable())->format(DATE_ATOM),
            ], Response::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $exception) {
            $this->logger->error('Analytics ingest failed.', [
                'source' => $source,
                'component' => self::COMPONENT,
                'duration_ms' => $this->durationMs($startedAt),
                'exception' => $exception,
            ]);

            return new JsonResponse([
                'error' => 'Analytics ingest unavailable.',
                'source' => $source,
                'component' => self::COMPONENT,
                'time' => (new \DateTimeImmutable())->format(DATE_ATOM),
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }
    }

    private function ingestCommon(string $source, Request $request): array
    {
        $payload = $this->decodeBody($request);
        $tenant = $this->normalizeIdentifier((string) ($request->headers->get('X-SR-TENANT') ?? 'unknown'), 'tenant header');
        $batch = isset($payload['batch']) && is_array($payload['batch']) ? array_values($payload['batch']) : [$payload];
        if (count($batch) > self::MAX_BATCH_SIZE) {
            throw new \InvalidArgumentException('Ingest batch contains too many items.');
        }

        $rows = [];
        $skipped = 0;

        foreach ($batch as $index => $item) {
            if (!is_array($item)) {
                ++$skipped;
                continue;
            }

            $type = $this->normalizeIdentifier((string) ($item['type'] ?? 'track'), 'event type');
            $event = $this->normalizeIdentifier((string) ($item['event'] ?? ('page' === $type ? 'page' : $type)), 'event name');
            $userId = $this->normalizeIdentifier((string) ($item['userId'] ?? $item['anonymousId'] ?? 'anon'), 'user id');
            $sessionId = $this->normalizeOptionalIdentifier($item['context']['sessionId'] ?? null, 'session id');
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

    private function normalizeOptionalIdentifier(mixed $value, string $field): string
    {
        if (null === $value || '' === $value) {
            return '';
        }

        return $this->normalizeIdentifier((string) $value, $field);
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
