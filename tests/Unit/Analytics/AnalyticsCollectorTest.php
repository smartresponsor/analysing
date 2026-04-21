<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Entity\Analytics\MetricSnapshot;
use App\Analysing\Service\Analytics\AnalyticsCollector;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class AnalyticsCollectorTest extends TestCase
{
    public function testRecordPersistsMetricSnapshot(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('persist')
            ->with(self::isInstanceOf(MetricSnapshot::class));
        $em->expects(self::once())->method('flush');

        $collector = new AnalyticsCollector($em, new NullLogger());
        $collector->record(
            'sales',
            10.5,
            new \DateTimeImmutable('2026-01-01 00:00:00'),
            new \DateTimeImmutable('2026-01-01 01:00:00'),
            ['tenant' => 'acme'],
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
