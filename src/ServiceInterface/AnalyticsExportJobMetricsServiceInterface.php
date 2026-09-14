<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface;

interface AnalyticsExportJobMetricsServiceInterface
{
    /**
     * @return array{jobs_total:int,status_counts:array{pending:int,running:int,done:int,failed:int},retryable_failed_jobs:int,avg_duration_ms:int,exported_rows_total:int}
     */
    public function snapshot(): array;
}
