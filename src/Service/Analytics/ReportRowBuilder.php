<?php

declare(strict_types=1);

namespace App\Service\Analytics;

use App\DTO\Analytics\KpiRequest;

final class ReportRowBuilder
{
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
