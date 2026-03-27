<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\ValueObject\Analytics\Segment;
use PHPUnit\Framework\TestCase;

final class SegmentValueObjectTest extends TestCase
{
    public function testTrimsAndStringifiesCode(): void
    {
        $segment = new Segment(' repeat-buyers ');

        self::assertSame('repeat-buyers', $segment->code());
        self::assertSame('repeat-buyers', (string) $segment);
    }

    public function testRejectsEmptyCode(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Segment('');
    }
}
