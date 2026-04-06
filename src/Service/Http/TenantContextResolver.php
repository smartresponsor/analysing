<?php

declare(strict_types=1);

namespace App\Service\Http;

use App\ServiceInterface\Http\TenantContextResolverInterface;
use Symfony\Component\HttpFoundation\Request;

final class TenantContextResolver implements TenantContextResolverInterface
{
    private const string ATTRIBUTE = '_analytics_tenant';
    private const string TOKEN_TENANT_ATTRIBUTE = '_analytics_token_tenant';
    private const string HEADER = 'X-SR-TENANT';

    public function resolve(Request $request): string
    {
        $existing = $request->attributes->get(self::ATTRIBUTE);
        if (is_string($existing) && '' !== trim($existing)) {
            return trim($existing);
        }

        $tokenTenant = $request->attributes->get(self::TOKEN_TENANT_ATTRIBUTE);
        if (is_string($tokenTenant) && '' !== trim($tokenTenant)) {
            return trim($tokenTenant);
        }

        $headerTenant = trim((string) $request->headers->get(self::HEADER, ''));
        if ('' !== $headerTenant) {
            return $headerTenant;
        }

        return 'public';
    }
}
