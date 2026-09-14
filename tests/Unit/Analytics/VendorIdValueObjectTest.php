<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\ValueObject\AnalyticsVendorId;
use PHPUnit\Framework\TestCase;

final class VendorIdValueObjectTest extends TestCase
{
    public function testTrimsAndStringifiesVendorId(): void
    {
        $vendorId = new AnalyticsVendorId(' demo-vendor ');

        self::assertSame('demo-vendor', $vendorId->value());
        self::assertSame('demo-vendor', (string) $vendorId);
    }

    public function testRejectsEmptyVendorId(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new AnalyticsVendorId('   ');
    }
}
