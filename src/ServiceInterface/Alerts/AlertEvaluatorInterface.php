<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface\Alerts;

use App\Analysing\Entity\Alerts\AlertRule;
use App\Analysing\Entity\Analytics\MetricSnapshot;

interface AlertEvaluatorInterface
{
    /**
     * @return array<array{rule: AlertRule, matched: bool, snapshot?: MetricSnapshot}>
     */
    public function evaluate(\DateTimeImmutable $from, \DateTimeImmutable $to): array;
}
