<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\Service\Http\AnalyticsRouteRateLimiter;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class AnalyticsRouteRateLimiterTest extends TestCase
{
    public function testLimiterAllowsThenRejectsWithinWindow(): void
    {
        $directory = sys_get_temp_dir().'/analytics-rate-limit-'.bin2hex(random_bytes(4));
        $limiter = new AnalyticsRouteRateLimiter(new NullLogger(), $directory, 60, 1, true);

        $first = $limiter->consume('analytics_flag_evaluate', 'tenant:acme');
        self::assertTrue($first->allowed);
        self::assertSame(1, $first->limit);
        self::assertSame(0, $first->remaining);
        self::assertSame('local_file', $first->mode);

        $second = $limiter->consume('analytics_flag_evaluate', 'tenant:acme');
        self::assertFalse($second->allowed);
        self::assertSame(1, $second->limit);
        self::assertSame(0, $second->remaining);
        self::assertGreaterThanOrEqual(1, $second->retryAfterSeconds);
    }
}
