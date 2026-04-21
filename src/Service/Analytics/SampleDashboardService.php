<?php

declare(strict_types=1);

namespace App\Analysing\Service\Analytics;

use App\Analysing\DTO\Analytics\KpiRequest;
use App\Analysing\ServiceInterface\Analytics\SampleAnalyticsDatasetInterface;
use App\Analysing\ServiceInterface\Analytics\SampleDashboardServiceInterface;
use Psr\Log\LoggerInterface;

final readonly class SampleDashboardService implements SampleDashboardServiceInterface
{
    public function __construct(
        private SampleAnalyticsDatasetInterface $dataset,
        private LoggerInterface $logger,
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
