<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface;

interface AnalyticsAccessGuardInterface
{
    public function allow(string $subject): bool;
}
