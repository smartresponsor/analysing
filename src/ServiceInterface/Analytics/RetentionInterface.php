<?php

declare(strict_types=1);

namespace App\ServiceInterface\Analytics;

interface RetentionInterface
{
    /**
     * @param list<array<string,mixed>> $rows
     *
     * @return list<array<string,mixed>>
     */
    public function prune(array $rows, int $maxDays): array;
}
