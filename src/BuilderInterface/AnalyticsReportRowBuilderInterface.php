<?php

declare(strict_types=1);

namespace App\Analysing\BuilderInterface;

use App\Analysing\DTO\AnalyticsKpiRequestDTO;

interface AnalyticsReportRowBuilderInterface
{
    /**
     * @param array{gross_minor:int,net_minor:int,margin_pct?:float|int|string,days:int} $kpi
     * @param list<array{date:string,gross_minor:int,net_minor:int}>                     $series
     *
     * @return list<array<string,scalar|null>>
     */
    public function build(AnalyticsKpiRequestDTO $request, array $kpi, array $series): array;
}
