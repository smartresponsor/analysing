<?php

declare(strict_types=1);

namespace App\ServiceInterface\Analytics;

use App\DTO\Analytics\KpiRequest;

interface DashboardServiceInterface
{
    /**
     * @return array{gross_minor:int, net_minor:int, days:int}
     */
    public function kpi(KpiRequest $req): array;

    /**
     * @return list<array{date:string, gross_minor:int, net_minor:int}>
     */
    public function timeseries(KpiRequest $req): array;

    /**
     * @return list<array{vendor_id:int, gross_minor:int, net_minor:int}>
     */
    public function byVendor(?string $currency = null, ?string $from = null, ?string $to = null): array;
}
