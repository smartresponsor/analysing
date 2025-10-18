<?php declare(strict_types=1);
namespace App\Controller\Analytics;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class DashboardPageController
{
    #[Route('/analytics/ping', name: 'analytics_ping')]
    public function ping(): Response { return new Response('ok', 200); }
}
