<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface;

use App\Analysing\Entity\Analytics\AnalyticsExportJobEntity;

interface AnalyticsReportGeneratorServiceInterface
{
    /**
     * @param array{
     *   from: string,
     *   to: string,
     *   vendorId?: int,
     *   currency?: string,
     *   format?: string
     * } $params
     */
    public function generate(array $params): AnalyticsExportJobEntity;
}
