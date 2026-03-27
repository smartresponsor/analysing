<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\Entity\Analytics\MetricSnapshot;
use PHPUnit\Framework\TestCase;

final class MetricSnapshotEntityTest extends TestCase
{
    public function testConstructsWithNormalizedDimensions(): void
    {
        $snapshot = new MetricSnapshot(
            'gross_revenue',
            12.5,
            new \DateTimeImmutable('2026-01-01 00:00:00'),
            new \DateTimeImmutable('2026-01-01 23:59:59'),
            ['currency' => 'USD', 'vendor_id' => 10],
        );

        self::assertSame('gross_revenue', $snapshot->getMetric());
        self::assertSame(12.5, $snapshot->getValue());
        self::assertSame(['currency' => 'USD', 'vendor_id' => 10], $snapshot->getDimensions());
    }

    public function testRejectsInvertedPeriod(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new MetricSnapshot(
            'gross_revenue',
            12.5,
            new \DateTimeImmutable('2026-01-02 00:00:00'),
            new \DateTimeImmutable('2026-01-01 00:00:00'),
        );
    }
}
