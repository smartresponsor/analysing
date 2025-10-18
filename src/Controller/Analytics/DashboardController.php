<?php declare(strict_types=1);
namespace App\Controller\Analytics;
use App\Service\Analytics\DashboardService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class DashboardController extends AbstractController
{
    public function __construct(private readonly DashboardService $dashboard) {}
    #[Route('/analytics/dashboard', name: 'analytics_dashboard')]
    public function index(): Response
    {
        $from = new \DateTimeImmutable('-7 days'); $to = new \DateTimeImmutable('now');
        $agg = $this->dashboard->aggregate('orders', $from, $to);
        return $this->render('analytics/index.html.twig', ['agg'=>$agg]);
    }
}
