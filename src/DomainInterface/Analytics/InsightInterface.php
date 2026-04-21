<?php

declare(strict_types=1);

namespace App\Analysing\DomainInterface\Analytics;

interface InsightInterface
{
    /**
     * @param array<string,mixed> $param
     *
     * @return array<string,mixed>
     */
    public function detectAnomaly(array $param): array;

    /**
     * @param array<string,mixed> $param
     *
     * @return array{
     *   name:string,
     *   nodes:list<array<string,mixed>>,
     *   edges:list<array{from:string,to:string}>
     * }
     */
    public function computeMetricTree(array $param): array;
}
