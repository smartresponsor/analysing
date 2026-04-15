<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\Entity\Analytics\ExportJob;
use PHPUnit\Framework\TestCase;

final class ExportJobPayloadTest extends TestCase
{
    public function testMergePayloadAddsRuntimeMetadata(): void
    {
        $job = new ExportJob('csv', ['from' => '2026-01-01']);

        $job->mergePayload(['export_path' => '/tmp/file.csv']);

        $payload = $job->getPayload();
        self::assertIsArray($payload);
        self::assertSame('/tmp/file.csv', $payload['export_path']);
        self::assertSame('2026-01-01', $payload['from']);
    }
}
