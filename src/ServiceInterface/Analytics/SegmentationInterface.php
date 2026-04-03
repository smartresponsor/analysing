<?php

declare(strict_types=1);

namespace App\ServiceInterface\Analytics;

use App\ValueObject\Analytics\Dimension;
use App\ValueObject\Analytics\Segment;

interface SegmentationInterface
{
    /**
     * @param list<array<string,mixed>> $rows
     *
     * @return list<array<string,mixed>>
     */
    public function apply(array $rows, Dimension $dim, Segment $seg): array;
}
