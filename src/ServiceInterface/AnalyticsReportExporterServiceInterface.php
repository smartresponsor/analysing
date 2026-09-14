<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface;

interface AnalyticsReportExporterServiceInterface
{
    /**
     * @param list<array<string,mixed>> $rows
     *
     * @return non-empty-string
     */
    public function export(array $rows, string $format = 'csv', ?string $dir = null): string;
}
