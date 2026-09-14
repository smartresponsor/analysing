<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface;

use App\Analysing\Entity\Analytics\AnalyticsExportJobEntity;

interface AnalyticsExportJobRunnerInterface
{
    public function run(AnalyticsExportJobEntity $job): AnalyticsExportJobEntity;
}
