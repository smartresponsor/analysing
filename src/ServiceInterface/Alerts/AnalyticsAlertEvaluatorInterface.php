<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface\Alerts;

use App\Analysing\Entity\Alerts\AnalyticsAlertRuleEntity;
use App\Analysing\Entity\Analytics\AnalyticsMetricSnapshotEntity;

interface AnalyticsAlertEvaluatorInterface
{
    /**
     * @return array<array{rule: AnalyticsAlertRuleEntity, matched: bool, snapshot?: AnalyticsMetricSnapshotEntity}>
     */
    public function evaluate(\DateTimeImmutable $from, \DateTimeImmutable $to): array;
}
