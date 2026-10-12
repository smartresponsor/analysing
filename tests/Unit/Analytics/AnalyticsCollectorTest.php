<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Entity\Analytics\AnalyticsMetricSnapshotEntity;
use App\Analysing\RepositoryInterface\AnalyticsMetricSnapshotRepositoryInterface;
use App\Analysing\Service\AnalyticsCollector;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class AnalyticsCollectorTest extends TestCase
{
    public function testRecordPersistsAnalyticsMetricSnapshotEntity(): void
    {
        $snapshots = $this->createMock(AnalyticsMetricSnapshotRepositoryInterface::class);
        $snapshots->expects(self::once())
            ->method('save')
            ->with(self::isInstanceOf(AnalyticsMetricSnapshotEntity::class));

        $collector = new AnalyticsCollector($snapshots, new NullLogger());
        $collector->record(
            'sales',
            10.5,
            new \DateTimeImmutable('2026-01-01 00:00:00'),
            new \DateTimeImmutable('2026-01-01 01:00:00'),
            ['vendor' => 'acme'],
        );
    }

    public function testRecordRejectsInvalidMetricName(): void
    {
        $snapshots = $this->createMock(AnalyticsMetricSnapshotRepositoryInterface::class);
        $collector = new AnalyticsCollector($snapshots, new NullLogger());

        $this->expectException(\InvalidArgumentException::class);
        $collector->record(
            '   ',
            10.5,
            new \DateTimeImmutable('2026-01-01 00:00:00'),
            new \DateTimeImmutable('2026-01-01 01:00:00'),
        );
    }
}
