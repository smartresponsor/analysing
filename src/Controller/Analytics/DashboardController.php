<?php
declare(strict_types=1);

namespace App\Controller\Analytics;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Service\Analytics\DashboardService;
use App\DTO\Analytics\KpiRequest;

final class DashboardController
{
    public function __construct(private readonly DashboardService $svc) {}

    public function kpi(Request $req): JsonResponse
    {
        $v = $req->query->get('vendorId');
        $dto = new KpiRequest(
            vendorId: $v !== null && $v !== '' ? (int)$v : null,
            currency: $req->query->get('currency'),
            from: $req->query->get('from'),
            to: $req->query->get('to'),
        );
        return new JsonResponse($this->svc->kpi($dto));
    }

    public function timeseries(Request $req): JsonResponse
    {
        $v = $req->query->get('vendorId');
        $dto = new KpiRequest(
            vendorId: $v !== null && $v !== '' ? (int)$v : null,
            currency: $req->query->get('currency'),
            from: $req->query->get('from'),
            to: $req->query->get('to'),
        );
        return new JsonResponse($this->svc->timeseries($dto));
    }

    public function topVendors(Request $req): JsonResponse
    {
        $currency = $req->query->get('currency');
        $from = $req->query->get('from');
        $to = $req->query->get('to');
        return new JsonResponse($this->svc->byVendor($currency, $from, $to));
    }
}
