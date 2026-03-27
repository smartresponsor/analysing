<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\ValueObject\Analytics\KpiId;
use PHPUnit\Framework\TestCase;

final class KpiIdValueObjectTest extends TestCase
{
    public function testTrimsAndStringifiesKpiId(): void
    {
        $kpiId = new KpiId(' revenue_total ');

        self::assertSame('revenue_total', $kpiId->value());
        self::assertSame('revenue_total', (string) $kpiId);
    }

    public function testRejectsEmptyKpiId(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new KpiId(' ');
    }
}
