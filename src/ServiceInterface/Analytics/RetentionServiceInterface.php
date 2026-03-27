<?php

declare(strict_types=1);

namespace App\ServiceInterface\Analytics;

interface RetentionServiceInterface extends RetentionInterface
{
    public function prune(array $rows, int $maxDays): array;
}
