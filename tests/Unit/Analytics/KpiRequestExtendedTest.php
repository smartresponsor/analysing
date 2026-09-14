<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\DTO\AnalyticsKpiRequestDTO;
use PHPUnit\Framework\TestCase;

final class KpiRequestExtendedTest extends TestCase
{
    public function testRejectsInvalidCurrency(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new AnalyticsKpiRequestDTO(1, 'usd1', '2026-01-01', '2026-01-02');
    }

    public function testRejectsNegativeVendorId(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new AnalyticsKpiRequestDTO(-1, 'USD', '2026-01-01', '2026-01-02');
    }

    public function testAllowsNullDates(): void
    {
        $request = new AnalyticsKpiRequestDTO(null, null, null, null);

        self::assertNull($request->from);
        self::assertNull($request->to);
    }
}
