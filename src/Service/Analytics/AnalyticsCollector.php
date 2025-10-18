<?php
declare(strict_types=1);

namespace App\Service\Analytics;

use App\Entity\Analytics\MetricSnapshot;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

final class AnalyticsCollector
{
    public function __construct(private readonly EntityManagerInterface $em, private readonly Connection $db) {}

    public function refresh(): int
    {
        $today = new DateTimeImmutable('today');
        $sql = "SELECT l.reference_id AS vendor_id, 'USD' as currency,
                       SUM(CASE WHEN l.direction='credit' THEN l.amount_minor ELSE 0 END) as gross,
                       SUM(CASE WHEN l.direction='debit' THEN l.amount_minor ELSE 0 END) as net
                  FROM ledger_entry l
              GROUP BY l.reference_id";
        $rows = $this->db->fetchAllAssociative($sql);
        $count = 0;

        foreach ($rows as $r) {
            $snap = new MetricSnapshot((int)$r['vendor_id'], $r['currency'], (int)$r['gross'], (int)$r['net'], (string)rand(0, 100));
            $this->em->persist($snap);
            $count++;
        }
        $this->em->flush();
        return $count;
    }
}
