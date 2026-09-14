<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface;

interface AnalyticsMetricIngestServiceInterface extends AnalyticsMetricIngestInterface
{
    /**
     * @return list<array<string,mixed>>
     */
    public function dumpBuffer(): array;
}
