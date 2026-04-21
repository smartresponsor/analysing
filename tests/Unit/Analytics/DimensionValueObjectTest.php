<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\ValueObject\Analytics\Dimension;
use PHPUnit\Framework\TestCase;

final class DimensionValueObjectTest extends TestCase
{
    public function testTrimsAndStringifiesName(): void
    {
        $dimension = new Dimension(' vendor ');

        self::assertSame('vendor', $dimension->name());
        self::assertSame('vendor', (string) $dimension);
    }

    public function testRejectsEmptyName(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Dimension('   ');
    }
}
