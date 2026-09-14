<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\ValueObject\AnalyticsSegment;
use PHPUnit\Framework\TestCase;

final class SegmentValueObjectTest extends TestCase
{
    public function testTrimsAndStringifiesCode(): void
    {
        $segment = new AnalyticsSegment(' repeat-buyers ');

        self::assertSame('repeat-buyers', $segment->code());
        self::assertSame('repeat-buyers', (string) $segment);
    }

    public function testRejectsEmptyCode(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new AnalyticsSegment('');
    }
}
