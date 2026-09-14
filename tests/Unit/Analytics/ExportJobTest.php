<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Entity\Analytics\AnalyticsExportJobEntity;
use PHPUnit\Framework\TestCase;

final class ExportJobTest extends TestCase
{
    public function testNormalizesTypeAndPayloadKeys(): void
    {
        $job = new AnalyticsExportJobEntity(' CSV ', [' vendor ' => 'acme', 'nested' => ['keep' => 1]]);

        self::assertSame('csv', $job->getType());
        self::assertSame(['vendor' => 'acme', 'nested' => ['keep' => 1]], $job->getPayload());
    }

    public function testTransitionsStatusAcrossLifecycle(): void
    {
        $job = new AnalyticsExportJobEntity('csv');

        self::assertSame(AnalyticsExportJobEntity::STATUS_PENDING, $job->getStatus());

        $job->start();
        self::assertSame(AnalyticsExportJobEntity::STATUS_RUNNING, $job->getStatus());
        self::assertNull($job->getFinishedAt());

        $job->done();
        self::assertSame(AnalyticsExportJobEntity::STATUS_DONE, $job->getStatus());
        self::assertNotNull($job->getFinishedAt());
        self::assertNull($job->getError());
    }

    public function testFailureNormalizesBlankMessage(): void
    {
        $job = new AnalyticsExportJobEntity('csv');
        $job->fail('   ');

        self::assertSame(AnalyticsExportJobEntity::STATUS_FAILED, $job->getStatus());
        self::assertSame('Unknown export job failure.', $job->getError());
        self::assertNotNull($job->getFinishedAt());
    }

    public function testAttemptsOverflowIsRejected(): void
    {
        $job = new AnalyticsExportJobEntity('csv');

        for ($i = 0; $i < 32767; ++$i) {
            $job->incAttempts();
        }

        $this->expectException(\InvalidArgumentException::class);
        $job->incAttempts();
    }

    public function testCanRetryOnlyForFailedJobsBelowLimit(): void
    {
        $job = new AnalyticsExportJobEntity('csv');

        self::assertFalse($job->canRetry());

        $job->fail('boom');
        self::assertTrue($job->canRetry());

        $job->incAttempts();
        $job->incAttempts();
        $job->incAttempts();

        self::assertFalse($job->canRetry());
    }
}
