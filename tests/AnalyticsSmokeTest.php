<?php

declare(strict_types=1);

namespace App\Analysing\Tests;

use App\Analysing\Entity\Analytics\AnalyticsMetricSnapshotEntity;
use PHPUnit\Framework\TestCase;

final class AnalyticsSmokeTest extends TestCase
{
    public function testEntityConstruct(): void
    {
        $m = new AnalyticsMetricSnapshotEntity(
            'gmv',
            42.5,
            new \DateTimeImmutable('2026-01-01 00:00:00'),
            new \DateTimeImmutable('2026-01-31 23:59:59'),
            ['currency' => 'USD']
        );

        self::assertSame('gmv', $m->getMetric());
        self::assertSame(42.5, $m->getValue());
    }
}
