<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface;

interface AnalyticsWindowQueryInterface
{
    /**
     * @param list<mixed> $rows
     *
     * @return list<list<mixed>>
     */
    public function window(array $rows, int $size): array;
}
