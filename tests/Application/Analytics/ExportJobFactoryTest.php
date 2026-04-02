<?php

declare(strict_types=1);

namespace App\Tests\Application\Analytics;

use App\Tests\Fixtures\Factories\ExportJobFactory;
use PHPUnit\Framework\TestCase;

/**
 * Ensures ExportJobFactory produces valid lifecycle states.
 */
final class ExportJobFactoryTest extends TestCase
{
    public function testPendingJob(): void
    {
        $job = ExportJobFactory::pending();

        self::assertSame('pending', $job->getStatus());
    }

    public function testCompletedJob(): void
    {
        $job = ExportJobFactory::completed();

        self::assertSame('done', $job->getStatus());
        self::assertNotNull($job->getPayload()['export_path'] ?? null);
    }

    public function testFailedJob(): void
    {
        $job = ExportJobFactory::failed();

        self::assertSame('failed', $job->getStatus());
        self::assertNotNull($job->getError());
    }
}
