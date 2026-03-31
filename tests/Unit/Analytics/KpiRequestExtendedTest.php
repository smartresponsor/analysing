<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\DTO\Analytics\KpiRequest;
use PHPUnit\Framework\TestCase;

final class KpiRequestExtendedTest extends TestCase
{
    public function testRejectsInvalidCurrency(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new KpiRequest(1, 'usd1', '2026-01-01', '2026-01-02');
    }

    public function testRejectsNegativeVendorId(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new KpiRequest(-1, 'USD', '2026-01-01', '2026-01-02');
    }

    public function testAllowsNullDates(): void
    {
        $request = new KpiRequest(null, null, null, null);

        self::assertNull($request->from);
        self::assertNull($request->to);
    }
}
