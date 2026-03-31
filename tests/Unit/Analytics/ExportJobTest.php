<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\Entity\Analytics\ExportJob;
use PHPUnit\Framework\TestCase;

final class ExportJobTest extends TestCase
{
    public function testNormalizesTypeAndPayloadKeys(): void
    {
        $job = new ExportJob(' CSV ', [' vendor ' => 'acme', 'nested' => ['keep' => 1]]);

        self::assertSame('csv', $job->getType());
        self::assertSame(['vendor' => 'acme', 'nested' => ['keep' => 1]], $job->getPayload());
    }

    public function testTransitionsStatusAcrossLifecycle(): void
    {
        $job = new ExportJob('csv');

        self::assertSame(ExportJob::STATUS_PENDING, $job->getStatus());

        $job->start();
        self::assertSame(ExportJob::STATUS_RUNNING, $job->getStatus());
        self::assertNull($job->getFinishedAt());

        $job->done();
        self::assertSame(ExportJob::STATUS_DONE, $job->getStatus());
        self::assertNotNull($job->getFinishedAt());
        self::assertNull($job->getError());
    }

    public function testFailureNormalizesBlankMessage(): void
    {
        $job = new ExportJob('csv');
        $job->fail('   ');

        self::assertSame(ExportJob::STATUS_FAILED, $job->getStatus());
        self::assertSame('Unknown export job failure.', $job->getError());
        self::assertNotNull($job->getFinishedAt());
    }

    public function testAttemptsOverflowIsRejected(): void
    {
        $job = new ExportJob('csv');

        for ($i = 0; $i < 32767; ++$i) {
            $job->incAttempts();
        }

        $this->expectException(\InvalidArgumentException::class);
        $job->incAttempts();
    }
}
