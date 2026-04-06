<?php

declare(strict_types=1);

namespace App\Service\Analytics;

use App\DTO\Analytics\KpiRequest;

/**
 * Builds report rows for KPIs and timeseries data.
 *
 * This service generates rows for both the total KPIs and individual timeseries data, which can be used in reporting and analysis.
 */
final class ReportRowBuilder
{
    /**
     * Builds an array of rows for the report based on KPI and timeseries data.
     *
     * @param KpiRequest                                                                    $request the KPI request containing the filters and options for the report
     * @param array{gross_minor:int, net_minor:int, margin_pct?:float|int|string, days:int} $kpi     KPI data including gross, net, margin, and the number of days
     * @param list<array{date:string, gross_minor:int, net_minor:int}>                      $series  the timeseries data containing date, gross, and net values
     *
     * @return list<array<string, scalar|null>> returns an array of rows, including totals and timeseries
     */
    public function build(KpiRequest $request, array $kpi, array $series): array
    {
        $rows = [[
            'section' => 'totals',
            'from' => $request->from ?? '',
            'to' => $request->to ?? '',
            'vendor_id' => null === $request->vendorId ? '' : (string) $request->vendorId,
            'currency' => $request->currency ?? '',
            'date' => '',
            'gross_minor' => $kpi['gross_minor'],
            'net_minor' => $kpi['net_minor'],
            'margin_pct' => $kpi['margin_pct'] ?? '',
            'days' => $kpi['days'],
        ]];

        foreach ($series as $point) {
            $rows[] = [
                'section' => 'timeseries',
                'from' => '',
                'to' => '',
                'vendor_id' => '',
                'currency' => '',
                'date' => $point['date'],
                'gross_minor' => $point['gross_minor'],
                'net_minor' => $point['net_minor'],
                'margin_pct' => '',
                'days' => '',
            ];
        }

        return $rows;
    }
}
