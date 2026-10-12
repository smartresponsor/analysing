<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SmartResponsor\Metrics\MetricRegistry;

/** Ensures legacy Prometheus histogram samples carry valid bucket labels. */
final class LegacyMetricRegistryTest extends TestCase
{
    public function testHistogramBucketsWithoutLabelsUseLeLabel(): void
    {
        require_once dirname(__DIR__, 2).'/tools/analytics/legacy/legacy-metrics.php';
        $metrics = new MetricRegistry();
        $metrics->histObserveMs('latency', [], 75, [50, 100]);
        $output = $metrics->renderProm();
        self::assertStringContainsString('latency_bucket{le="100"} 1', $output);
        self::assertStringNotContainsString('{,le=', $output);
    }

    public function testHistogramBucketsWithLabelsUseSingleSeparator(): void
    {
        require_once dirname(__DIR__, 2).'/tools/analytics/legacy/legacy-metrics.php';
        $metrics = new MetricRegistry();
        $metrics->histObserveMs('latency', ['region' => 'us'], 75, [100]);
        $output = $metrics->renderProm();
        self::assertStringContainsString('latency_bucket{region="us",le="100"} 1', $output);
        self::assertStringNotContainsString(',,le=', $output);
    }
}
