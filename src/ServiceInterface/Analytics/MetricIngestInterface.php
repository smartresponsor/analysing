<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface\Analytics;

interface MetricIngestInterface
{
    /**
     * @param array<string,mixed> $payload
     */
    public function ingest(array $payload): void;
}
