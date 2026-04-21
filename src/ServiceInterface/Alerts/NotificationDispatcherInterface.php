<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface\Alerts;

use App\Analysing\Entity\Alerts\AlertRule;

interface NotificationDispatcherInterface
{
    public function dispatch(AlertRule $rule, string $message): void;
}
