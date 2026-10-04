<?php

declare(strict_types=1);

namespace App\Analysing\Repository;

use App\Analysing\Entity\Alerts\AnalyticsAlertRuleEntity;
use App\Analysing\Entity\Analytics\AnalyticsMetricSnapshotEntity;
use App\Analysing\RepositoryInterface\AnalyticsMetricSnapshotRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final readonly class AnalyticsMetricSnapshotRepository implements AnalyticsMetricSnapshotRepositoryInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(AnalyticsMetricSnapshotEntity $snapshot): void
    {
        $this->entityManager->persist($snapshot);
        $this->entityManager->flush();
    }

    public function findActiveAlertRules(): array
    {
        $rules = $this->entityManager->getRepository(AnalyticsAlertRuleEntity::class)->findBy(['isActive' => true]);

        return array_values($rules);
    }

    public function findLatestInRange(
        string $metric,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
    ): ?AnalyticsMetricSnapshotEntity {
        $result = $this->entityManager->createQueryBuilder()
            ->select('snapshot')
            ->from(AnalyticsMetricSnapshotEntity::class, 'snapshot')
            ->andWhere('snapshot.metric = :metric')
            ->andWhere('snapshot.periodStart >= :from')
            ->andWhere('snapshot.periodEnd <= :to')
            ->orderBy('snapshot.periodEnd', 'DESC')
            ->addOrderBy('snapshot.periodStart', 'DESC')
            ->setMaxResults(1)
            ->setParameter('metric', $metric)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getOneOrNullResult();

        return $result instanceof AnalyticsMetricSnapshotEntity ? $result : null;
    }
}
