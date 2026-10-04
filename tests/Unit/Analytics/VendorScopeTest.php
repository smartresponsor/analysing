<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Service\AnalyticsVendorScope;
use App\Analysing\ValueObject\AnalyticsVendorId;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class VendorScopeTest extends TestCase
{
    public function testFilterReturnsOnlyRowsForVendor(): void
    {
        $service = new AnalyticsVendorScope(new NullLogger());
        $rows = $service->filter([
            ['vendor' => 'acme', 'metric' => 'sales'],
            ['vendor' => 'other', 'metric' => 'sales'],
            ['metric' => 'missing'],
        ], new AnalyticsVendorId('acme'));

        self::assertCount(1, $rows);
        self::assertSame('acme', $rows[0]['vendor']);
    }

    public function testFilterSkipsMalformedVendorRows(): void
    {
        $service = new AnalyticsVendorScope(new NullLogger());
        $rows = $service->filter([
            ['vendor' => ['not-scalar']],
            ['vendor' => str_repeat('v', 129)],
            ['vendor' => ' acme ', 'metric' => 'sales'],
        ], new AnalyticsVendorId('acme'));

        self::assertCount(1, $rows);
        self::assertSame(' acme ', $rows[0]['vendor']);
    }

    public function testFilterRejectsOverlongVendorIdentifier(): void
    {
        $service = new AnalyticsVendorScope(new NullLogger());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Vendor identifier exceeds the maximum allowed length.');

        $service->filter([], new AnalyticsVendorId(str_repeat('v', 129)));
    }
}
