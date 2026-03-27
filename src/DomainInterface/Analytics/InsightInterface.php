<?php

declare(strict_types=1);

namespace App\DomainInterface\Analytics;

interface InsightInterface
{
    /**
     * @param array{tenant_id:mixed,event_name:mixed,days:mixed} $param
     *
     * @return array<string,mixed>
     */
    public function detectAnomaly(array $param): array;

    /**
     * @param array{name:mixed} $param
     *
     * @return array{
     *   name:string,
     *   nodes:list<array<string,mixed>>,
     *   edges:list<array{from:string,to:string}>
     * }
     */
    public function computeMetricTree(array $param): array;
}
