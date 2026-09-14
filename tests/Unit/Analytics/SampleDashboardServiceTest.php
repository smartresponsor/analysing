<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\DTO\AnalyticsKpiRequestDTO;
use App\Analysing\Service\AnalyticsSampleAnalyticsDataset;
use App\Analysing\Service\AnalyticsSampleDashboardService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class SampleDashboardServiceTest extends TestCase
{
    public function testKpiReturnsStableSamplePayload(): void
    {
        $service = new AnalyticsSampleDashboardService(new AnalyticsSampleAnalyticsDataset(), new NullLogger());

        $payload = $service->kpi(new AnalyticsKpiRequestDTO(vendorId: 101, currency: 'USD', from: '2026-01-01 00:00:00', to: '2026-01-07 23:59:59'));

        self::assertSame(76650, $payload['gross_minor']);
        self::assertSame(52122, $payload['net_minor']);
        self::assertSame(68.0, $payload['margin_pct']);
        self::assertSame(7, $payload['days']);
    }

    public function testTimeseriesReturnsSevenRows(): void
    {
        $service = new AnalyticsSampleDashboardService(new AnalyticsSampleAnalyticsDataset(), new NullLogger());

        $rows = $service->timeseries(new AnalyticsKpiRequestDTO());

        self::assertCount(7, $rows);
        self::assertSame('2026-01-01', $rows[0]['date']);
        self::assertSame(18000, $rows[0]['gross_minor']);
    }
}
