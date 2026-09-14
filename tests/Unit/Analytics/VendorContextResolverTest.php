<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Resolver\Http\AnalyticsVendorContextResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class VendorContextResolverTest extends TestCase
{
    public function testResolvePrefersTokenVendorOverHeader(): void
    {
        $resolver = new AnalyticsVendorContextResolver();
        $request = Request::create('/analytics/health', 'GET');
        $request->headers->set('X-SR-VENDOR', 'acme');
        $request->attributes->set('_analytics_token_vendor', 'enterprise');

        self::assertSame('enterprise', $resolver->resolve($request));
    }

    public function testResolveFallsBackToPublic(): void
    {
        $resolver = new AnalyticsVendorContextResolver();
        $request = Request::create('/analytics/health', 'GET');

        self::assertSame('public', $resolver->resolve($request));
    }
}
