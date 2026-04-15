<?php

declare(strict_types=1);

namespace App\Service\Analytics;

use App\ServiceInterface\Analytics\LocalCacheInterface;
use Psr\Log\LoggerInterface;

final class LocalCache implements LocalCacheInterface
{
    private const int MAX_TTL = 86400;
    private const int MAX_KEY_LENGTH = 256;
    private const int MAX_ENTRIES = 1000;
    private const int MAX_VALUE_BYTES = 65536;

    /** @var array<string, array{v:mixed,exp:int,stored_at:int}> */
    private array $data = [];

    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    /**
     * @throws \Throwable
     */
    public function get(string $key, callable $fallback, int $ttl = 60): mixed
    {
        $key = trim($key);
        if ('' === $key) {
            $this->logger->warning('Analytics local cache received an empty cache key. Bypassing cache lookup.');

            return $this->runFallback($fallback, '[empty-key]');
        }

        if (strlen($key) > self::MAX_KEY_LENGTH) {
            $this->logger->warning('Analytics local cache received an overlong cache key. Bypassing cache lookup.', [
                'key_length' => strlen($key),
                'max_key_length' => self::MAX_KEY_LENGTH,
            ]);

            return $this->runFallback($fallback, '[overlong-key]');
        }

        $now = $this->now();
        $this->purgeExpired($now);
        $hit = $this->data[$key] ?? null;

        if (is_array($hit) && $hit['exp'] > $now) {
            $this->logger->info('Analytics local cache hit.', [
                'key' => $key,
                'ttl_remaining' => max(0, $hit['exp'] - $now),
            ]);

            return $hit['v'];
        }

        if ($ttl <= 0) {
            $this->logger->warning('Analytics local cache received non-positive TTL. Bypassing cache store.', [
                'key' => $key,
                'ttl' => $ttl,
            ]);

            return $this->runFallback($fallback, $key);
        }

        if ($ttl > self::MAX_TTL) {
            $this->logger->warning('Analytics local cache TTL exceeded maximum and will be clamped.', [
                'key' => $key,
                'ttl' => $ttl,
                'max_ttl' => self::MAX_TTL,
            ]);
            $ttl = self::MAX_TTL;
        }

        $this->logger->info('Analytics local cache miss.', [
            'key' => $key,
            'ttl' => $ttl,
        ]);
        $value = $this->runFallback($fallback, $key);
        $this->store($key, $value, $now + $ttl, $now);

        return $value;
    }

    public function clear(): void
    {
        $count = count($this->data);
        $this->data = [];
        $this->logger->info('Analytics local cache cleared.', ['entries' => $count]);
    }

    private function purgeExpired(int $now): void
    {
        $purged = 0;
        foreach ($this->data as $key => $item) {
            if ($item['exp'] <= $now) {
                unset($this->data[$key]);
                ++$purged;
            }
        }

        if ($purged > 0) {
            $this->logger->info('Analytics local cache purged expired entries.', [
                'entries' => $purged,
            ]);
        }
    }

    private function store(string $key, mixed $value, int $expiresAt, int $storedAt): void
    {
        $estimatedBytes = $this->estimateValueBytes($value);
        if (null !== $estimatedBytes && $estimatedBytes > self::MAX_VALUE_BYTES) {
            $this->logger->warning('Analytics local cache skipped storing an oversized value.', [
                'key' => $key,
                'estimated_bytes' => $estimatedBytes,
                'max_value_bytes' => self::MAX_VALUE_BYTES,
            ]);

            return;
        }

        $this->evictIfNeeded();
        $this->data[$key] = [
            'v' => $value,
            'exp' => $expiresAt,
            'stored_at' => $storedAt,
        ];

        $this->logger->info('Analytics local cache stored a value.', [
            'key' => $key,
            'estimated_bytes' => $estimatedBytes,
            'entries' => count($this->data),
        ]);
    }

    private function evictIfNeeded(): void
    {
        if (count($this->data) < self::MAX_ENTRIES) {
            return;
        }

        $oldestKey = null;
        $oldestStoredAt = null;
        foreach ($this->data as $key => $item) {
            $storedAt = $item['stored_at'];
            if (null === $oldestStoredAt || $storedAt < $oldestStoredAt) {
                $oldestStoredAt = $storedAt;
                $oldestKey = $key;
            }
        }

        if (null !== $oldestKey) {
            unset($this->data[$oldestKey]);
            $this->logger->warning('Analytics local cache evicted the oldest entry because the cache reached its maximum size.', [
                'evicted_key' => $oldestKey,
                'max_entries' => self::MAX_ENTRIES,
            ]);
        }
    }

    /**
     * @throws \Throwable
     */
    private function runFallback(callable $fallback, string $key): mixed
    {
        try {
            return $fallback();
        } catch (\Throwable $exception) {
            $this->logger->error('Analytics local cache fallback threw an exception.', [
                'key' => $key,
                'exception' => $exception,
            ]);

            throw $exception;
        }
    }

    private function estimateValueBytes(mixed $value): ?int
    {
        try {
            return strlen(serialize($value));
        } catch (\Throwable $exception) {
            $this->logger->warning('Analytics local cache could not estimate a value size.', [
                'exception' => $exception,
            ]);

            return null;
        }
    }

    private function now(): int
    {
        return time();
    }
}
