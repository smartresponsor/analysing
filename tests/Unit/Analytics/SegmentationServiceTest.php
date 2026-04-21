<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Service\Analytics\SegmentationService;
use App\Analysing\ValueObject\Analytics\Dimension;
use App\Analysing\ValueObject\Analytics\Segment;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class SegmentationServiceTest extends TestCase
{
    public function testApplyReturnsOnlyMatchingRows(): void
    {
        $service = new SegmentationService(new NullLogger());

        $result = $service->apply([
            ['tenant' => 'acme', 'value' => 1],
            ['tenant' => 'beta', 'value' => 2],
            ['value' => 3],
        ], new Dimension('tenant'), new Segment('acme'));

        self::assertCount(1, $result);
        self::assertSame('acme', $result[0]['tenant']);
    }
}
