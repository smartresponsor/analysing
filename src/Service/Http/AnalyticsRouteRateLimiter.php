<?php

declare(strict_types=1);

namespace App\Service\Http;

use App\ValueObject\Http\AnalyticsRateLimitDecision;
use App\ServiceInterface\Http\AnalyticsRouteRateLimiterInterface;
use Psr\Log\LoggerInterface;

final class AnalyticsRouteRateLimiter implements AnalyticsRouteRateLimiterInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly string $directory,
        private readonly int $windowSeconds = 60,
        private readonly int $defaultWriteLimit = 60,
        private readonly bool $enabled = true,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function consume(string $route, string $scope): AnalyticsRateLimitDecision
    {
        if (!$this->enabled) {
            return new AnalyticsRateLimitDecision(true, 0, 0, 0, time(), $scope, 'disabled');
        }

        $limit = max(1, $this->defaultWriteLimit);
        $now = time();
        $path = $this->bucketPath($route, $scope);
        $this->ensureDirectory();

        $handle = fopen($path, 'c+');
        if (false === $handle) {
            throw new \RuntimeException('Unable to open analytics rate limit bucket.');
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new \RuntimeException('Unable to lock analytics rate limit bucket.');
            }

            $hits = $this->readHits($handle);
            $windowStart = $now - $this->windowSeconds + 1;
            $hits = array_values(array_filter($hits, static fn (int $hit): bool => $hit >= $windowStart));
            sort($hits);

            $count = count($hits);
            if ($count >= $limit) {
                $oldest = $hits[0] ?? $now;
                $resetAt = $oldest + $this->windowSeconds;
                $retryAfter = max(1, $resetAt - $now);
                $this->writeHits($handle, $hits);

                $this->logger->warning('Analytics route rate limit exceeded.', [
                    'route' => $route,
                    'scope' => $scope,
                    'limit' => $limit,
                    'window_seconds' => $this->windowSeconds,
                ]);

                return new AnalyticsRateLimitDecision(false, $limit, 0, $retryAfter, $resetAt, $scope, 'local_file');
            }

            $hits[] = $now;
            sort($hits);
            $this->writeHits($handle, $hits);

            $oldest = $hits[0] ?? $now;
            $resetAt = $oldest + $this->windowSeconds;
            $remaining = max(0, $limit - count($hits));

            return new AnalyticsRateLimitDecision(true, $limit, $remaining, 0, $resetAt, $scope, 'local_file');
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private function ensureDirectory(): void
    {
        if (is_dir($this->directory)) {
            return;
        }

        if (!@mkdir($this->directory, 0775, true) && !is_dir($this->directory)) {
            $this->logger->error('Analytics rate limiter could not create directory.', [
                'directory' => $this->directory,
            ]);

            throw new \RuntimeException('Unable to create analytics rate limit directory.');
        }
    }

    private function bucketPath(string $route, string $scope): string
    {
        $hash = hash('sha256', $route.'|'.$scope);

        return rtrim($this->directory, '/').'/'.$hash.'.json';
    }

    /**
     * @param resource $handle
     *
     * @return list<int>
     */
    private function readHits($handle): array
    {
        rewind($handle);
        $raw = stream_get_contents($handle);
        if (false === $raw) {
            throw new \RuntimeException('Unable to read analytics rate limit bucket.');
        }

        $trimmed = trim($raw);
        if ('' === $trimmed) {
            return [];
        }

        try {
            $decoded = json_decode($trimmed, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            $this->logger->warning('Analytics rate limiter detected invalid bucket JSON and will reset it.', [
                'exception' => $exception,
            ]);

            return [];
        }

        if (!is_array($decoded)) {
            return [];
        }

        $hits = [];
        foreach ($decoded as $hit) {
            if (is_int($hit)) {
                $hits[] = $hit;
            }
        }

        return $hits;
    }

    /**
     * @param resource $handle
     * @param list<int> $hits
     */
    private function writeHits($handle, array $hits): void
    {
        rewind($handle);
        ftruncate($handle, 0);

        $encoded = json_encode(array_values($hits), JSON_THROW_ON_ERROR);
        fwrite($handle, $encoded);
        fflush($handle);
    }
}
