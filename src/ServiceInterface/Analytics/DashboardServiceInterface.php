<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface\Analytics;

use App\Analysing\DTO\Analytics\KpiRequest;

interface DashboardServiceInterface
{
    /**
     * @return array{gross_minor:int, net_minor:int, margin_pct:float, days:int}
     */
    public function kpi(KpiRequest $req): array;

    /**
     * @return list<array{date:string, gross_minor:int, net_minor:int}>
     */
    public function timeseries(KpiRequest $req): array;

    /**
     * @return list<array{vendor_id:int, gross_minor:int, net_minor:int, margin_pct:float}>
     */
    public function byVendor(?string $currency = null, ?string $from = null, ?string $to = null): array;
}
