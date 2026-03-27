<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\Service\Analytics\SegmentationService;
use App\ValueObject\Analytics\Dimension;
use App\ValueObject\Analytics\Segment;
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
