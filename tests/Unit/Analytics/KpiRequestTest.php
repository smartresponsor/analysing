<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\DTO\Analytics\KpiRequest;
use PHPUnit\Framework\TestCase;

final class KpiRequestTest extends TestCase
{
    public function testNormalizesCurrencyAndDateRange(): void
    {
        $request = new KpiRequest(42, ' usd ', '2026-01-01T12:00:00+00:00', '2026-01-31 23:59:59');

        self::assertSame(42, $request->vendorId);
        self::assertSame('USD', $request->currency);
        self::assertSame('2026-01-01 12:00:00', $request->from);
        self::assertSame('2026-01-31 23:59:59', $request->to);
    }

    public function testRejectsInvalidOrderedRange(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new KpiRequest(1, 'USD', '2026-02-01 00:00:00', '2026-01-01 00:00:00');
    }
}
