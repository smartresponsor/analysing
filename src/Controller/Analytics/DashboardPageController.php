<?php
declare(strict_types=1);

namespace App\Controller\Analytics;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Service\Analytics\DashboardService;
use App\DTO\Analytics\KpiRequest;

final class DashboardPageController extends AbstractController
{
    public function __construct(private readonly DashboardService $svc) {}

    public function index(Request $req): Response
    {
        $v = $req->query->get('vendorId');
        $dto = new KpiRequest(
            vendorId: $v !== null && $v !== '' ? (int)$v : null,
            currency: $req->query->get('currency'),
            from: $req->query->get('from'),
            to: $req->query->get('to'),
        );

        $kpi = $this->svc->kpi($dto);
        $series = $this->svc->timeseries($dto);
        $top = $this->svc->byVendor($dto->currency, $dto->from, $dto->to);

        return $this->render('analytics/index.html.twig', [
            'kpi' => $kpi,
            'series' => $series,
            'top' => $top,
            'params' => ['vendorId' => $dto->vendorId, 'currency' => $dto->currency, 'from' => $dto->from, 'to' => $dto->to],
        ]);
    }
}
