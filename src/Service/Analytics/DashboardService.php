<?php declare(strict_types=1);
namespace App\Service\Analytics;
use Doctrine\ORM\EntityManagerInterface;

final class DashboardService
{
    public function __construct(private readonly EntityManagerInterface $em) {}
    /** @return array{metric:string,total:float,from:\DateTimeImmutable,to:\DateTimeImmutable} */
    public function aggregate(string $metric, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $conn = $this->em->getConnection();
        $sql = "SELECT SUM(value) as total FROM analytics_metric_snapshot WHERE metric = :metric AND period_start >= :from AND period_end <= :to";
        $r = $conn->fetchAssociative($sql, ['metric'=>$metric, 'from'=>$from->format('Y-m-d H:i:s'), 'to'=>$to->format('Y-m-d H:i:s')]);
        return ['metric'=>$metric, 'total'=>(float)($r['total'] ?? 0), 'from'=>$from, 'to'=>$to];
    }
}
