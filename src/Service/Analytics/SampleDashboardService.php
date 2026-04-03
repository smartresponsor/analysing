<?php

declare(strict_types=1);

namespace App\Service\Analytics;

use App\DTO\Analytics\KpiRequest;
use App\ServiceInterface\Analytics\DashboardServiceInterface;
use Psr\Log\LoggerInterface;

final class SampleDashboardService implements DashboardServiceInterface
{
    public function __construct(
        private readonly SampleAnalyticsDataset $dataset,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function kpi(KpiRequest $req): array
    {
        $payload = $this->dataset->kpi($req);
        $this->logger->info('Analytics sample dashboard KPI query completed.', [
            'vendor_id' => $req->vendorId,
            'currency' => $req->currency,
            'from' => $req->from,
            'to' => $req->to,
        ]);

        return $payload;
    }

    public function timeseries(KpiRequest $req): array
    {
        $payload = $this->dataset->timeseries($req);
        $this->logger->info('Analytics sample dashboard timeseries query completed.', [
            'vendor_id' => $req->vendorId,
            'currency' => $req->currency,
            'from' => $req->from,
            'to' => $req->to,
            'rows' => count($payload),
        ]);

        return $payload;
    }

    public function byVendor(?string $currency = null, ?string $from = null, ?string $to = null): array
    {
        $payload = $this->dataset->byVendor($currency, $from, $to);
        $this->logger->info('Analytics sample dashboard by-vendor query completed.', [
            'currency' => $currency,
            'from' => $from,
            'to' => $to,
            'rows' => count($payload),
        ]);

        return $payload;
    }
}
