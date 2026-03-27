<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\Entity\Analytics\ExportJob;
use PHPUnit\Framework\TestCase;

final class ExportJobEntityTest extends TestCase
{
    public function testLifecycleTransitionsUpdateState(): void
    {
        $job = new ExportJob('CSV', ['vendor_id' => 10]);

        self::assertSame('csv', $job->getType());
        self::assertSame(ExportJob::STATUS_PENDING, $job->getStatus());

        $job->incAttempts();
        $job->start();
        $job->done();

        self::assertSame(1, $job->getAttempts());
        self::assertSame(ExportJob::STATUS_DONE, $job->getStatus());
        self::assertNull($job->getError());
        self::assertNotNull($job->getFinishedAt());
    }

    public function testIncAttemptsRejectsOverflow(): void
    {
        $job = new ExportJob('csv');
        $reflection = new \ReflectionProperty($job, 'attempts');
        $reflection->setAccessible(true);
        $reflection->setValue($job, 32767);

        $this->expectException(\InvalidArgumentException::class);
        $job->incAttempts();
    }
}
