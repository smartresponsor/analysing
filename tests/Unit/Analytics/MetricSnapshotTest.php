<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\Entity\Analytics\MetricSnapshot;
use PHPUnit\Framework\TestCase;

final class MetricSnapshotTest extends TestCase
{
    public function testNormalizesMetricAndDimensionKeys(): void
    {
        $snapshot = new MetricSnapshot(
            ' revenue ',
            12.5,
            new \DateTimeImmutable('2026-03-01 00:00:00'),
            new \DateTimeImmutable('2026-03-31 23:59:59'),
            [' region ' => 'us', 'channel' => 'ads'],
        );

        self::assertSame('revenue', $snapshot->getMetric());
        self::assertSame(['region' => 'us', 'channel' => 'ads'], $snapshot->getDimensions());
        self::assertNotNull($snapshot->getCreatedAt());
    }

    public function testRejectsInvalidPeriodOrder(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new MetricSnapshot(
            'revenue',
            10.0,
            new \DateTimeImmutable('2026-04-01 00:00:00'),
            new \DateTimeImmutable('2026-03-01 00:00:00'),
        );
    }

    public function testRejectsInvalidDimensionValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new MetricSnapshot(
            'revenue',
            10.0,
            new \DateTimeImmutable('2026-03-01 00:00:00'),
            new \DateTimeImmutable('2026-03-31 23:59:59'),
            ['region' => new \stdClass()],
        );
    }
}
