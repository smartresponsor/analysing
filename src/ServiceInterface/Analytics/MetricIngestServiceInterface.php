<?php

declare(strict_types=1);

namespace App\ServiceInterface\Analytics;

interface MetricIngestServiceInterface extends MetricIngestInterface
{
    /**
     * @return list<array<string,mixed>>
     */
    public function dumpBuffer(): array;
}
