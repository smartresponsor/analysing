<?php

declare(strict_types=1);

namespace App\Tests\Application\Analytics\Support;

/**
 * Provides reusable assertions for analytics API-style response contracts.
 */
trait AnalyticsContractAssertions
{
    /**
     * @param array<string,mixed> $payload
     */
    private function assertExportJobContract(array $payload): void
    {
        self::assertArrayHasKey('id', $payload);
        self::assertArrayHasKey('type', $payload);
        self::assertArrayHasKey('status', $payload);
        self::assertArrayHasKey('attempts', $payload);
        self::assertArrayHasKey('error', $payload);
        self::assertArrayHasKey('created_at', $payload);
        self::assertArrayHasKey('finished_at', $payload);
        self::assertArrayHasKey('payload', $payload);
        self::assertArrayHasKey('download_url', $payload);

        self::assertTrue(null === $payload['id'] || is_int($payload['id']));
        self::assertIsString($payload['type']);
        self::assertContains($payload['status'], ['pending', 'running', 'done', 'failed']);
        self::assertIsInt($payload['attempts']);
        self::assertTrue(null === $payload['error'] || is_string($payload['error']));
        self::assertIsString($payload['created_at']);
        self::assertTrue(null === $payload['finished_at'] || is_string($payload['finished_at']));
        self::assertIsArray($payload['payload']);
        self::assertTrue(null === $payload['download_url'] || is_string($payload['download_url']));
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function assertMetricsContract(array $payload): void
    {
        self::assertSame([
            'jobs_total',
            'status_counts',
            'retryable_failed_jobs',
            'avg_duration_ms',
            'exported_rows_total',
        ], array_keys($payload));

        self::assertIsInt($payload['jobs_total']);
        self::assertIsArray($payload['status_counts']);
        self::assertSame(['pending', 'running', 'done', 'failed'], array_keys($payload['status_counts']));

        foreach ($payload['status_counts'] as $count) {
            self::assertIsInt($count);
        }

        self::assertIsInt($payload['retryable_failed_jobs']);
        self::assertIsInt($payload['avg_duration_ms']);
        self::assertIsInt($payload['exported_rows_total']);
    }
}
