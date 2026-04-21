<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\DTO\Analytics\KpiRequest;
use App\Analysing\Service\Analytics\ReportRowBuilder;
use PHPUnit\Framework\TestCase;

final class ReportRowBuilderTest extends TestCase
{
    public function testBuildIncludesStableColumnsForTotalsAndTimeseriesRows(): void
    {
        $builder = new ReportRowBuilder();
        $request = new KpiRequest(vendorId: 42, currency: 'usd', from: '2026-03-01 00:00:00', to: '2026-03-31 23:59:59');

        $rows = $builder->build(
            $request,
            ['gross_minor' => 1000, 'net_minor' => 800, 'margin_pct' => 20.0, 'days' => 31],
            [['date' => '2026-03-10', 'gross_minor' => 100, 'net_minor' => 80]],
        );

        self::assertCount(2, $rows);
        self::assertSame(['section', 'from', 'to', 'vendor_id', 'currency', 'date', 'gross_minor', 'net_minor', 'margin_pct', 'days'], array_keys($rows[0]));
        self::assertSame('timeseries', $rows[1]['section']);
        self::assertSame('2026-03-10', $rows[1]['date']);
        self::assertArrayHasKey('margin_pct', $rows[1]);
    }
}
