<?php

declare(strict_types=1);

namespace App\ServiceInterface\Analytics;

interface DashboardHtmlRendererInterface
{
    /**
     * @param array{gross_minor:int, net_minor:int, margin_pct:float|int, days:int}            $kpi
     * @param list<array{date:string, gross_minor:int, net_minor:int}>                         $series
     * @param list<array{vendor_id:int, gross_minor:int, net_minor:int, margin_pct:float|int}> $top
     * @param array<string,int|string|null>                                                    $params
     */
    public function renderDashboard(array $kpi, array $series, array $top, array $params): string;

    public function renderError(string $title, string $message, int $status): string;
}
