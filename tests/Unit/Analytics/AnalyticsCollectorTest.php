<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Entity\Analytics\AnalyticsMetricSnapshotEntity;
use App\Analysing\Service\AnalyticsCollector;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class AnalyticsCollectorTest extends TestCase
{
    public function testRecordPersistsAnalyticsMetricSnapshotEntity(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('persist')
            ->with(self::isInstanceOf(AnalyticsMetricSnapshotEntity::class));
        $em->expects(self::once())->method('flush');

        $collector = new AnalyticsCollector($em, new NullLogger());
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
        $em = $this->createMock(EntityManagerInterface::class);
        $collector = new AnalyticsCollector($em, new NullLogger());

        $this->expectException(\InvalidArgumentException::class);
        $collector->record(
            '   ',
            10.5,
            new \DateTimeImmutable('2026-01-01 00:00:00'),
            new \DateTimeImmutable('2026-01-01 01:00:00'),
        );
    }
}
