<?php

declare(strict_types=1);

namespace App\ServiceInterface\Analytics;

use App\ValueObject\Analytics\Dimension;
use App\ValueObject\Analytics\Segment;

interface SegmentationInterface
{
    public function apply(array $rows, Dimension $dim, Segment $seg): array;
}
