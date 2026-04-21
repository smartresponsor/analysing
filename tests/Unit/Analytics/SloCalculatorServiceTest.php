<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Service\Analytics\SloCalculator;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class SloCalculatorServiceTest extends TestCase
{
    public function testAvailabilityUsesOnlyNumericFiniteValues(): void
    {
        $service = new SloCalculator(new NullLogger());

        $availability = $service->availability([1, 0, '1', 'broken', INF]);

        self::assertSame(2 / 3, $availability);
    }

    public function testAvailabilityReturnsZeroForNoUsableValues(): void
    {
        $service = new SloCalculator(new NullLogger());

        self::assertSame(0.0, $service->availability(['broken', null]));
    }
}
