<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface;

use App\Analysing\ValueObject\AnalyticsDimension;
use App\Analysing\ValueObject\AnalyticsSegment;

interface AnalyticsSegmentationServiceInterface extends AnalyticsSegmentationInterface
{
    /**
     * @param list<array<string,mixed>> $rows
     *
     * @return list<array<string,mixed>>
     */
    public function apply(array $rows, AnalyticsDimension $dim, AnalyticsSegment $seg): array;
}
