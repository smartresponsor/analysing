<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\ValueObject\AnalyticsKpiId;
use PHPUnit\Framework\TestCase;

final class KpiIdValueObjectTest extends TestCase
{
    public function testTrimsAndStringifiesKpiId(): void
    {
        $kpiId = new AnalyticsKpiId(' revenue_total ');

        self::assertSame('revenue_total', $kpiId->value());
        self::assertSame('revenue_total', (string) $kpiId);
    }

    public function testRejectsEmptyKpiId(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new AnalyticsKpiId(' ');
    }
}
