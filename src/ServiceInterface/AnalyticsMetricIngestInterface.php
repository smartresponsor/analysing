<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface;

interface AnalyticsMetricIngestInterface
{
    /**
     * @param array<string,mixed> $payload
     */
    public function ingest(array $payload): void;
}
