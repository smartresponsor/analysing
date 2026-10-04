<?php

declare(strict_types=1);

namespace App\Analysing\RepositoryInterface;

use App\Analysing\Entity\Alerts\AnalyticsAlertRuleEntity;
use App\Analysing\Entity\Analytics\AnalyticsMetricSnapshotEntity;

interface AnalyticsMetricSnapshotRepositoryInterface
{
    public function save(AnalyticsMetricSnapshotEntity $snapshot): void;

    /** @return list<AnalyticsAlertRuleEntity> */
    public function findActiveAlertRules(): array;

    public function findLatestInRange(
        string $metric,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
    ): ?AnalyticsMetricSnapshotEntity;
}
