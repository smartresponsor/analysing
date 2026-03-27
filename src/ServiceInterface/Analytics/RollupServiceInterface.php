<?php

declare(strict_types=1);

namespace App\ServiceInterface\Analytics;

interface RollupServiceInterface extends RollupInterface
{
    /**
     * @param list<array<string,mixed>> $rows
     */
    public function sum(array $rows, string $field): float|int;
}
