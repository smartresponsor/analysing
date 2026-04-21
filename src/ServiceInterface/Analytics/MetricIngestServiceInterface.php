<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface\Analytics;

interface MetricIngestServiceInterface extends MetricIngestInterface
{
    /**
     * @return list<array<string,mixed>>
     */
    public function dumpBuffer(): array;
}
