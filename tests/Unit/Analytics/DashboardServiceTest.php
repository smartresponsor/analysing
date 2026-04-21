<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\DTO\Analytics\KpiRequest;
use App\Analysing\Service\Analytics\DashboardService;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class DashboardServiceTest extends TestCase
{
    public function testKpiMapsAssociativeRow(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects(self::once())
            ->method('fetchAssociative')
            ->willReturn([
                'gross' => '1200',
                'net' => '900',
                'days' => '3',
            ]);

        $service = new DashboardService($connection, new NullLogger());
        $result = $service->kpi(new KpiRequest(null, 'usd', '2026-01-01 00:00:00', '2026-01-03 23:59:59'));

        self::assertSame(1200, $result['gross_minor']);
        self::assertSame(900, $result['net_minor']);
        self::assertSame(75.0, $result['margin_pct']);
        self::assertSame(3, $result['days']);
    }

    public function testTimeseriesRejectsMissingDateField(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->willReturn([
                ['gross' => 100, 'net' => 90],
            ]);

        $service = new DashboardService($connection, new NullLogger());

        $this->expectException(\RuntimeException::class);
        $service->timeseries(new KpiRequest());
    }
}
