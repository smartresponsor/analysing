<?php

declare(strict_types=1);

namespace App\ServiceInterface\Analytics;

interface RollupInterface
{
    public function sum(array $rows, string $field): float|int;
}
