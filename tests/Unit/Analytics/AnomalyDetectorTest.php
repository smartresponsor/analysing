<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Service\AnalyticsAnomalyDetector;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class AnomalyDetectorTest extends TestCase
{
    public function testZscoreReturnsScoresForNumericSeries(): void
    {
        $service = new AnalyticsAnomalyDetector(new NullLogger());
        $scores = $service->zscore([1, 2, 3]);

        self::assertCount(3, $scores);
        self::assertEqualsWithDelta(0.0, array_sum($scores), 0.00001);
    }

    public function testZscoreRejectsTooManyValues(): void
    {
        $service = new AnalyticsAnomalyDetector(new NullLogger());

        $this->expectException(\InvalidArgumentException::class);
        $service->zscore(array_fill(0, 10001, 1));
    }
}
