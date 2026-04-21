<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface\Analytics;

use App\Analysing\ValueObject\Analytics\Dimension;
use App\Analysing\ValueObject\Analytics\Segment;

interface SegmentationServiceInterface extends SegmentationInterface
{
    /**
     * @param list<array<string,mixed>> $rows
     *
     * @return list<array<string,mixed>>
     */
    public function apply(array $rows, Dimension $dim, Segment $seg): array;
}
