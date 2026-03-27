<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\Service\Analytics\KpiRegistry;
use App\ValueObject\Analytics\KpiId;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class KpiRegistryTest extends TestCase
{
    public function testRegistryKeepsOnlyValidEntries(): void
    {
        $registry = new KpiRegistry([
            'orders_per_day' => 'Orders',
            'bad id' => 'Should be ignored',
            'revenue_total' => '',
        ], new NullLogger());

        self::assertSame([
            'orders_per_day' => 'Orders',
        ], $registry->list());
        self::assertTrue($registry->has(new KpiId('orders_per_day')));
        self::assertFalse($registry->has(new KpiId('missing')));
    }
}
