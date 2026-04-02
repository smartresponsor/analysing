<?php

declare(strict_types=1);

namespace App\Tests\Fixtures\Factories;

use App\Entity\Analytics\ExportJob;

/**
 * Factory for creating ExportJob entities in various lifecycle states.
 */
final class ExportJobFactory
{
    /**
     * Creates a pending export job.
     *
     * @param array<string,mixed> $payload
     */
    public static function pending(array $payload = []): ExportJob
    {
        return new ExportJob('csv', $payload);
    }

    /**
     * Creates a completed export job with realistic metadata.
     *
     * @param array<string,mixed> $payload
     */
    public static function completed(array $payload = []): ExportJob
    {
        $job = new ExportJob('csv', $payload);
        $job->incAttempts();
        $job->start();

        $job->mergePayload([
            'export_path' => '/tmp/export.csv',
            'row_count' => 10,
            'duration_ms' => 123,
        ]);

        $job->done();

        return $job;
    }

    /**
     * Creates a failed export job.
     *
     * @param array<string,mixed> $payload
     */
    public static function failed(array $payload = []): ExportJob
    {
        $job = new ExportJob('csv', $payload);
        $job->incAttempts();
        $job->start();
        $job->fail('Synthetic failure');

        return $job;
    }
}
