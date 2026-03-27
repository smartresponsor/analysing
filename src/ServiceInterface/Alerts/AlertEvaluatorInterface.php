<?php

declare(strict_types=1);

namespace App\ServiceInterface\Alerts;

use App\Entity\Alerts\AlertRule;
use App\Entity\Analytics\MetricSnapshot;

interface AlertEvaluatorInterface
{
    /**
     * @return array<array{rule: AlertRule, matched: bool, snapshot?: MetricSnapshot}>
     */
    public function evaluate(\DateTimeImmutable $from, \DateTimeImmutable $to): array;
}
