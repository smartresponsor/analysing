<?php declare(strict_types=1);
namespace App\Service\Analytics;
use App\Entity\Analytics\MetricSnapshot;
use Doctrine\ORM\EntityManagerInterface;

final class AnalyticsCollector
{
    public function __construct(private readonly EntityManagerInterface $em) {}
    public function record(string $metric, float $value, \DateTimeImmutable $from, \DateTimeImmutable $to, ?array $dimensions = null): void
    { $snap = new MetricSnapshot($metric,$value,$from,$to,$dimensions); $this->em->persist($snap); $this->em->flush(); }
}
