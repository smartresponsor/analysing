<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface\Alerts;

use App\Analysing\Entity\Alerts\AnalyticsAlertRuleEntity;

interface AnalyticsNotificationDispatcherInterface
{
    public function dispatch(AnalyticsAlertRuleEntity $rule, string $message): void;
}
