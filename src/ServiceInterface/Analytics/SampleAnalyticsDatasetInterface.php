<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface\Analytics;

use App\Analysing\DTO\Analytics\KpiRequest;

interface SampleAnalyticsDatasetInterface
{
    /** @return array{gross_minor:int,net_minor:int,margin_pct:float,days:int} */
    public function kpi(KpiRequest $request): array;

    /** @return list<array{date:string,gross_minor:int,net_minor:int}> */
    public function timeseries(KpiRequest $request): array;

    /** @return list<array{vendor_id:int,gross_minor:int,net_minor:int,margin_pct:float}> */
    public function byVendor(?string $currency = null, ?string $from = null, ?string $to = null): array;

    /**
     * @param list<string> $steps
     *
     * @return list<array{day:string,user_count:int}>
     */
    public function funnel(string $app, string $env, array $steps, \DateTimeImmutable $from, \DateTimeImmutable $to): array;

    /** @return list<array{day_offset:int,active_user:int}> */
    public function retention(string $app, string $env, \DateTimeImmutable $cohort, int $days): array;

    /** @return list<array{from_event:string,to_event:string,transition_count:int}> */
    public function path(string $app, string $env, \DateTimeImmutable $day, int $top): array;
}
