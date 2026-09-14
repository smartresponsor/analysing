<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface;

interface AnalyticsRetentionServiceInterface extends AnalyticsRetentionInterface
{
    public function prune(array $rows, int $maxDays): array;
}
