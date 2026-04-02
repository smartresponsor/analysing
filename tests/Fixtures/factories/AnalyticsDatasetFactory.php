<?php

declare(strict_types=1);

namespace App\Tests\Fixtures\Factories;

/**
 * Builds deterministic KPI and timeseries datasets for analytics tests.
 */
final class AnalyticsDatasetFactory
{
    /**
     * @return array{gross_minor:int, net_minor:int, margin_pct:float, days:int}
     */
    public static function kpi(): array
    {
        return [
            'gross_minor' => 100000,
            'net_minor' => 75000,
            'margin_pct' => 25.0,
            'days' => 30,
        ];
    }

    /**
     * @return list<array{date:string, gross_minor:int, net_minor:int}>
     */
    public static function timeseries(): array
    {
        return [
            ['date' => '2026-01-01', 'gross_minor' => 1000, 'net_minor' => 800],
            ['date' => '2026-01-02', 'gross_minor' => 1200, 'net_minor' => 900],
            ['date' => '2026-01-03', 'gross_minor' => 900, 'net_minor' => 700],
        ];
    }
}
