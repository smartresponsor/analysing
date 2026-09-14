<?php

declare(strict_types=1);

namespace App\Analysing\FactoryInterface;

use App\Analysing\DTO\AnalyticsKpiRequestDTO;
use Symfony\Component\HttpFoundation\Request;

interface AnalyticsDashboardRequestFactoryInterface
{
    public function fromRequest(Request $request): AnalyticsKpiRequestDTO;
}
