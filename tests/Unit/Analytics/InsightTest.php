<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\RepositoryInterface\AnalyticsRepositoryInterface;
use App\Analysing\Service\AnalyticsInsight;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class InsightTest extends TestCase
{
    public function testDetectAnomalyReturnsInsufficientDataForShortSeries(): void
    {
        $repository = $this->createMock(AnalyticsRepositoryInterface::class);
        $repository->expects(self::once())
            ->method('fetchAnomalySeries')
            ->with('purchase', 7)
            ->willReturn(array_fill(0, 6, ['user_count' => 5]));

        $service = new AnalyticsInsight($repository, new NullLogger());
        $result = $service->detectAnomaly([
            'vendor_id' => 'vendor-1',
            'event_name' => 'purchase',
            'days' => 7,
        ]);

        self::assertFalse($result['anomaly']);
        self::assertSame('insufficient-data', $result['reason']);
    }

    public function testDetectAnomalyRejectsInvalidUserCountRows(): void
    {
        $repository = $this->createMock(AnalyticsRepositoryInterface::class);
        $repository->method('fetchAnomalySeries')->willReturn([['user_count' => 'bad']]);
        $service = new AnalyticsInsight($repository, new NullLogger());

        $this->expectException(\RuntimeException::class);
        $service->detectAnomaly([
            'vendor_id' => 'vendor-1',
            'event_name' => 'purchase',
            'days' => 7,
        ]);
    }

    public function testComputeMetricTreeRejectsUnknownTree(): void
    {
        $repository = $this->createMock(AnalyticsRepositoryInterface::class);
        $repository->expects(self::never())->method('fetchLatestMetricValue');
        $service = new AnalyticsInsight($repository, new NullLogger());

        $this->expectException(\InvalidArgumentException::class);
        $service->computeMetricTree([
            'vendor_id' => 'vendor-1',
            'name' => 'unknown-tree',
        ]);
    }
}
