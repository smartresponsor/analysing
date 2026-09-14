<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Service\AnalyticsKpiRegistry;
use App\Analysing\ValueObject\AnalyticsKpiId;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class KpiRegistryTest extends TestCase
{
    public function testRegistryKeepsOnlyValidEntries(): void
    {
        $registry = new AnalyticsKpiRegistry(new NullLogger(), [
            'orders_per_day' => 'Orders',
            'bad id' => 'Should be ignored',
            'revenue_total' => '',
        ]);

        self::assertSame([
            'orders_per_day' => 'Orders',
        ], $registry->list());
        self::assertTrue($registry->has(new AnalyticsKpiId('orders_per_day')));
        self::assertFalse($registry->has(new AnalyticsKpiId('missing')));
    }
}
