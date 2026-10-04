<?php

declare(strict_types=1);

namespace App\Analysing\Repository;

use App\Analysing\Entity\Analytics\AnalyticsExportJobEntity;
use App\Analysing\RepositoryInterface\AnalyticsExportJobRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final readonly class AnalyticsExportJobRepository implements AnalyticsExportJobRepositoryInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function find(int $id): ?AnalyticsExportJobEntity
    {
        $job = $this->entityManager->getRepository(AnalyticsExportJobEntity::class)->find($id);

        return $job instanceof AnalyticsExportJobEntity ? $job : null;
    }

    public function findByStatus(string $status, int $limit): array
    {
        $jobs = $this->entityManager->getRepository(AnalyticsExportJobEntity::class)->findBy(
            ['status' => $status],
            ['createdAt' => 'ASC'],
            $limit,
        );

        return array_values($jobs);
    }

    public function findRecent(int $limit): array
    {
        $jobs = $this->entityManager->getRepository(AnalyticsExportJobEntity::class)->findBy(
            [],
            ['createdAt' => 'DESC'],
            $limit,
        );

        return array_values($jobs);
    }

    public function save(AnalyticsExportJobEntity $job): void
    {
        $this->entityManager->persist($job);
        $this->entityManager->flush();
    }

    public function flush(): void
    {
        $this->entityManager->flush();
    }
}
