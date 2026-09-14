<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Entity\Analytics\AnalyticsExportJobEntity;
use PHPUnit\Framework\TestCase;

final class ExportJobEntityTest extends TestCase
{
    public function testLifecycleTransitionsUpdateState(): void
    {
        $job = new AnalyticsExportJobEntity('CSV', ['vendor_id' => 10]);

        self::assertSame('csv', $job->getType());
        self::assertSame(AnalyticsExportJobEntity::STATUS_PENDING, $job->getStatus());

        $job->incAttempts();
        $job->start();
        $job->done();

        self::assertSame(1, $job->getAttempts());
        self::assertSame(AnalyticsExportJobEntity::STATUS_DONE, $job->getStatus());
        self::assertNull($job->getError());
        self::assertNotNull($job->getFinishedAt());
    }

    public function testIncAttemptsRejectsOverflow(): void
    {
        $job = new AnalyticsExportJobEntity('csv');
        $reflection = new \ReflectionProperty($job, 'attempts');
        $reflection->setAccessible(true);
        $reflection->setValue($job, 32767);

        $this->expectException(\InvalidArgumentException::class);
        $job->incAttempts();
    }
}
