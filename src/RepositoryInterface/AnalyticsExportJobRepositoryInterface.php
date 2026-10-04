<?php

declare(strict_types=1);

namespace App\Analysing\RepositoryInterface;

use App\Analysing\Entity\Analytics\AnalyticsExportJobEntity;

interface AnalyticsExportJobRepositoryInterface
{
    public function find(int $id): ?AnalyticsExportJobEntity;

    /** @return list<AnalyticsExportJobEntity> */
    public function findByStatus(string $status, int $limit): array;

    /** @return list<AnalyticsExportJobEntity> */
    public function findRecent(int $limit): array;

    public function save(AnalyticsExportJobEntity $job): void;

    public function flush(): void;
}
