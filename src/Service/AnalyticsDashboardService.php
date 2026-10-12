<?php

declare(strict_types=1);

namespace App\Analysing\Service;

use App\Analysing\DTO\AnalyticsKpiRequestDTO;
use App\Analysing\RepositoryInterface\AnalyticsDashboardRepositoryInterface;
use App\Analysing\ServiceInterface\AnalyticsDashboardServiceInterface;

final readonly class AnalyticsDashboardService implements AnalyticsDashboardServiceInterface
{
    public function __construct(private AnalyticsDashboardRepositoryInterface $repository)
    {
    }

    public function kpi(AnalyticsKpiRequestDTO $req): array
    {
        return $this->repository->kpi($req);
    }

    public function timeseries(AnalyticsKpiRequestDTO $req): array
    {
        return $this->repository->timeseries($req);
    }

    public function byVendor(?string $currency = null, ?string $from = null, ?string $to = null): array
    {
        return $this->repository->byVendor($currency, $from, $to);
    }
}
