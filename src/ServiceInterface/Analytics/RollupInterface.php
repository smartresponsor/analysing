<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface\Analytics;

interface RollupInterface
{
    /** @param list<array<string,mixed>> $rows */
    public function sum(array $rows, string $field): float|int;
}
