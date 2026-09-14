<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Entity\Analytics\AnalyticsMetricSnapshotEntity;
use PHPUnit\Framework\TestCase;

final class MetricSnapshotTest extends TestCase
{
    public function testNormalizesMetricAndDimensionKeys(): void
    {
        $snapshot = new AnalyticsMetricSnapshotEntity(
            ' revenue ',
            12.5,
            new \DateTimeImmutable('2026-03-01 00:00:00'),
            new \DateTimeImmutable('2026-03-31 23:59:59'),
            [' region ' => 'us', 'channel' => 'ads'],
        );

        self::assertSame('revenue', $snapshot->getMetric());
        self::assertSame(['region' => 'us', 'channel' => 'ads'], $snapshot->getDimensions());
    }

    public function testRejectsInvalidPeriodOrder(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new AnalyticsMetricSnapshotEntity(
            'revenue',
            10.0,
            new \DateTimeImmutable('2026-04-01 00:00:00'),
            new \DateTimeImmutable('2026-03-01 00:00:00'),
        );
    }

    public function testRejectsInvalidDimensionValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new AnalyticsMetricSnapshotEntity(
            'revenue',
            10.0,
            new \DateTimeImmutable('2026-03-01 00:00:00'),
            new \DateTimeImmutable('2026-03-31 23:59:59'),
            ['region' => new \stdClass()],
        );
    }
}
