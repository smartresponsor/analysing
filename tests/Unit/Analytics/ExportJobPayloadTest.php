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

        self::assertSame('/tmp/file.csv', $job->getPayload()['export_path']);
        self::assertSame('2026-01-01', $job->getPayload()['from']);
    }
}
