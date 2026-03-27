<?php

declare(strict_types=1);

namespace App\ServiceInterface\Analytics;

use App\Entity\Analytics\ExportJob;

interface ReportGeneratorServiceInterface
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
    public function generate(array $params): ExportJob;
}
