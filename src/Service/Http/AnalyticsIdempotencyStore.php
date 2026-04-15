<?php

declare(strict_types=1);

namespace App\Service\Http;

use App\ServiceInterface\Http\AnalyticsIdempotencyStoreInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

final class AnalyticsIdempotencyStore implements AnalyticsIdempotencyStoreInterface
{
    private const int MAX_KEY_LENGTH = 255;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly string $directory,
        private readonly int $ttlSeconds = 86400,
    ) {
    }

    public function isValidKey(string $key): bool
    {
        $trimmed = trim($key);
        if ('' === $trimmed || strlen($trimmed) > self::MAX_KEY_LENGTH) {
            return false;
        }

        return 1 === preg_match('/\A[A-Za-z0-9._:\/-]+\z/', $trimmed);
    }

    /**
     * @return array{
     *   status: 'new'|'replay'|'conflict'|'pending',
     *   record?: array<string,mixed>
     * }
     */
    public function begin(string $route, string $tenant, string $key, string $requestFingerprint): array
    {
        $path = $this->recordPath($route, $tenant, $key);
        $this->ensureDirectory();

        $handle = fopen($path, 'c+');
        if (false === $handle) {
            throw new \RuntimeException('Unable to open analytics idempotency record.');
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new \RuntimeException('Unable to lock analytics idempotency record.');
            }

            $record = $this->readFromHandle($handle);
            if (null !== $record && $this->isExpired($record)) {
                $record = null;
                $this->truncateHandle($handle);
            }

            if (null === $record) {
                $record = $this->newPendingRecord($route, $tenant, $key, $requestFingerprint);
                $this->writeToHandle($handle, $record);

                return ['status' => 'new', 'record' => $record];
            }

            $storedFingerprint = $record['request_fingerprint'] ?? null;
            if (!is_string($storedFingerprint) || !hash_equals($storedFingerprint, $requestFingerprint)) {
                return ['status' => 'conflict', 'record' => $record];
            }

            $state = $record['state'] ?? 'pending';
            if ('completed' === $state && isset($record['response']) && is_array($record['response'])) {
                return ['status' => 'replay', 'record' => $record];
            }

            return ['status' => 'pending', 'record' => $record];
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    public function finalize(string $route, string $tenant, string $key, string $requestFingerprint, Response $response): void
    {
        $path = $this->recordPath($route, $tenant, $key);
        $handle = fopen($path, 'c+');
        if (false === $handle) {
            throw new \RuntimeException('Unable to finalize analytics idempotency record.');
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new \RuntimeException('Unable to lock analytics idempotency record for finalize.');
            }

            $record = $this->readFromHandle($handle);
            if (null === $record) {
                return;
            }

            $storedFingerprint = $record['request_fingerprint'] ?? null;
            if (!is_string($storedFingerprint) || !hash_equals($storedFingerprint, $requestFingerprint)) {
                return;
            }

            $statusCode = $response->getStatusCode();
            if ($statusCode >= 500 || Response::HTTP_TOO_MANY_REQUESTS === $statusCode) {
                $this->truncateHandle($handle);

                return;
            }

            $record['state'] = 'completed';
            $record['completed_at'] = gmdate(DATE_ATOM);
            $record['response'] = [
                'status' => $statusCode,
                'content_type' => $response->headers->get('Content-Type', 'application/json'),
                'body_base64' => base64_encode((string) $response->getContent()),
            ];

            $this->writeToHandle($handle, $record);
        } catch (\JsonException) {
            $this->truncateHandle($handle);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /** @param array<string,mixed> $record */
    public function buildReplayResponse(array $record): Response
    {
        $response = $record['response'] ?? null;
        if (!is_array($response)) {
            throw new \RuntimeException('Analytics idempotency replay record is missing response data.');
        }

        $status = $response['status'] ?? null;
        $contentType = $response['content_type'] ?? null;
        $bodyBase64 = $response['body_base64'] ?? null;
        if (!is_int($status) || !is_string($contentType) || !is_string($bodyBase64)) {
            throw new \RuntimeException('Analytics idempotency replay record is malformed.');
        }

        $body = base64_decode($bodyBase64, true);
        if (false === $body) {
            throw new \RuntimeException('Analytics idempotency replay body could not be decoded.');
        }

        $replayed = new Response($body, $status);
        $replayed->headers->set('Content-Type', $contentType);

        return $replayed;
    }

    private function ensureDirectory(): void
    {
        if (is_dir($this->directory)) {
            return;
        }

        if (!@mkdir($this->directory, 0775, true) && !is_dir($this->directory)) {
            $this->logger->error('Analytics idempotency store could not create directory.', [
                'directory' => $this->directory,
            ]);

            throw new \RuntimeException('Unable to create analytics idempotency directory.');
        }
    }

    private function recordPath(string $route, string $tenant, string $key): string
    {
        $hash = hash('sha256', $route.'|'.$tenant.'|'.$key);

        return rtrim($this->directory, '/').'/'.$hash.'.json';
    }

    /**
     * @param resource $handle
     *
     * @return array<string,mixed>|null
     */
    private function readFromHandle($handle): ?array
    {
        rewind($handle);
        $raw = stream_get_contents($handle);
        if (false === $raw) {
            throw new \RuntimeException('Unable to read analytics idempotency record.');
        }

        $trimmed = trim($raw);
        if ('' === $trimmed) {
            return null;
        }

        try {
            $decoded = json_decode($trimmed, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            $this->logger->warning('Analytics idempotency store detected invalid JSON and will reset the record.', [
                'exception' => $exception,
            ]);

            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }

    /** @param array<string,mixed> $record */
    private function isExpired(array $record): bool
    {
        $expiresAt = $record['expires_at'] ?? null;
        if (!is_string($expiresAt)) {
            return true;
        }

        $expiration = strtotime($expiresAt);
        if (false === $expiration) {
            return true;
        }

        return $expiration <= time();
    }

    /**
     * @return array{version:int,state:string,route:string,tenant:string,key:string,request_fingerprint:string,created_at:string,expires_at:string}
     */
    private function newPendingRecord(string $route, string $tenant, string $key, string $requestFingerprint): array
    {
        $now = time();

        return [
            'version' => 1,
            'state' => 'pending',
            'route' => $route,
            'tenant' => $tenant,
            'key' => $key,
            'request_fingerprint' => $requestFingerprint,
            'created_at' => gmdate(DATE_ATOM, $now),
            'expires_at' => gmdate(DATE_ATOM, $now + $this->ttlSeconds),
        ];
    }

    /**
     * @param resource            $handle
     * @param array<string,mixed> $record
     *
     * @throws \JsonException
     */
    private function writeToHandle($handle, array $record): void
    {
        $encoded = json_encode($record, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
        $this->truncateHandle($handle);

        $written = fwrite($handle, $encoded);
        if (false === $written) {
            throw new \RuntimeException('Unable to write analytics idempotency record.');
        }

        fflush($handle);
    }

    /** @param resource $handle */
    private function truncateHandle($handle): void
    {
        ftruncate($handle, 0);
        rewind($handle);
    }
}
