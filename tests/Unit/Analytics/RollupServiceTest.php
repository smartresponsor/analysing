<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\Service\Analytics\RollupService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class RollupServiceTest extends TestCase
{
    public function testRollupSumsNumericFieldValues(): void
    {
        $service = new RollupService(new NullLogger());

        self::assertSame(3.5, $service->rollup([
            ['value' => 1],
            ['value' => '2.5'],
            ['value' => 'not-numeric'],
        ], 'value'));
    }

    public function testRollupRejectsEmptyField(): void
    {
        $service = new RollupService(new NullLogger());

        $this->expectException(\InvalidArgumentException::class);
        $service->rollup([], '   ');
    }
}
