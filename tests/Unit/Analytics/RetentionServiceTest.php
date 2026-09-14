<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Service\AnalyticsRetentionService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class RetentionServiceTest extends TestCase
{
    public function testPruneKeepsRowsWithinThreshold(): void
    {
        $now = time();
        $service = new AnalyticsRetentionService(new NullLogger());

        $rows = $service->prune([
            ['ts' => $now],
            ['ts' => $now - 86400],
            ['ts' => 'bad'],
        ], 2);

        self::assertCount(2, $rows);
    }

    public function testPruneRejectsNonPositiveMaxDays(): void
    {
        $service = new AnalyticsRetentionService(new NullLogger());

        $this->expectException(\InvalidArgumentException::class);
        $service->prune([], 0);
    }
}
