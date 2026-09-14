<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Entity\Analytics\AnalyticsExportJobEntity;
use PHPUnit\Framework\TestCase;

final class ExportJobPayloadTest extends TestCase
{
    public function testMergePayloadAddsRuntimeMetadata(): void
    {
        $job = new AnalyticsExportJobEntity('csv', ['from' => '2026-01-01']);

        $job->mergePayload(['export_path' => '/tmp/file.csv']);

        $payload = $job->getPayload();
        self::assertIsArray($payload);
        self::assertSame('/tmp/file.csv', $payload['export_path']);
        self::assertSame('2026-01-01', $payload['from']);
    }
}
