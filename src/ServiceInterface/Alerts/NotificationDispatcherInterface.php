<?php

declare(strict_types=1);

namespace App\ServiceInterface\Alerts;

use App\Entity\Alerts\AlertRule;

interface NotificationDispatcherInterface
{
    public function dispatch(AlertRule $rule, string $message): void;
}
