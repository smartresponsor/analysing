<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Service\Http\TenantContextResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class TenantContextResolverTest extends TestCase
{
    public function testResolvePrefersTokenTenantOverHeader(): void
    {
        $resolver = new TenantContextResolver();
        $request = Request::create('/analytics/health', 'GET');
        $request->headers->set('X-SR-TENANT', 'acme');
        $request->attributes->set('_analytics_token_tenant', 'enterprise');

        self::assertSame('enterprise', $resolver->resolve($request));
    }

    public function testResolveFallsBackToPublic(): void
    {
        $resolver = new TenantContextResolver();
        $request = Request::create('/analytics/health', 'GET');

        self::assertSame('public', $resolver->resolve($request));
    }
}
