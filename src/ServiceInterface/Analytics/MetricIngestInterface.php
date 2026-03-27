<?php

declare(strict_types=1);

namespace App\ServiceInterface\Analytics;

interface MetricIngestInterface
{
    /**
     * @param array<string, scalar|list<scalar|null>|null> $payload
     */
    public function ingest(array $payload): void;
}
