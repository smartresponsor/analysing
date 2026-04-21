<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface\Analytics;

interface WindowQueryInterface
{
    /**
     * @param list<mixed> $rows
     *
     * @return list<list<mixed>>
     */
    public function window(array $rows, int $size): array;
}
