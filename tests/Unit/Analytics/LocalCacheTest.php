<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Service\AnalyticsLocalCache;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class LocalCacheTest extends TestCase
{
    public function testCacheStoresValueForNormalizedKey(): void
    {
        $cache = new AnalyticsLocalCache(new NullLogger());
        $calls = 0;

        $fallback = static function () use (&$calls): string {
            ++$calls;

            return 'cached-value';
        };

        self::assertSame('cached-value', $cache->get('orders', $fallback, 60));
        self::assertSame('cached-value', $cache->get('orders', $fallback, 60));
        self::assertSame(1, $calls);
    }

    public function testOverlongKeyBypassesCaching(): void
    {
        $cache = new AnalyticsLocalCache(new NullLogger());
        $calls = 0;
        $key = str_repeat('k', 257);

        $fallback = static function () use (&$calls): string {
            ++$calls;

            return 'value';
        };

        self::assertSame('value', $cache->get($key, $fallback, 60));
        self::assertSame('value', $cache->get($key, $fallback, 60));
        self::assertSame(2, $calls);
    }
}
