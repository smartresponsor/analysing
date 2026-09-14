<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Service\AnalyticsSegmentationService;
use App\Analysing\ValueObject\AnalyticsDimension;
use App\Analysing\ValueObject\AnalyticsSegment;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class SegmentationServiceTest extends TestCase
{
    public function testApplyReturnsOnlyMatchingRows(): void
    {
        $service = new AnalyticsSegmentationService(new NullLogger());

        $result = $service->apply([
            ['vendor' => 'acme', 'value' => 1],
            ['vendor' => 'beta', 'value' => 2],
            ['value' => 3],
        ], new AnalyticsDimension('vendor'), new AnalyticsSegment('acme'));

        self::assertCount(1, $result);
        self::assertSame('acme', $result[0]['vendor']);
    }
}
