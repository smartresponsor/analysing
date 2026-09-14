<?php

declare(strict_types=1);

namespace App\Analysing\Resolver\Http;

use Symfony\Component\HttpFoundation\Request;

final class AnalyticsVendorContextResolver implements AnalyticsVendorContextResolverInterface
{
    private const string ATTRIBUTE = '_analytics_vendor';
    private const string TOKEN_VENDOR_ATTRIBUTE = '_analytics_token_vendor';
    private const string HEADER = 'X-SR-VENDOR';

    public function resolve(Request $request): string
    {
        $existing = $request->attributes->get(self::ATTRIBUTE);
        if (is_string($existing) && '' !== trim($existing)) {
            return trim($existing);
        }

        $tokenVendor = $request->attributes->get(self::TOKEN_VENDOR_ATTRIBUTE);
        if (is_string($tokenVendor) && '' !== trim($tokenVendor)) {
            return trim($tokenVendor);
        }

        $headerVendor = trim((string) $request->headers->get(self::HEADER, ''));
        if ('' !== $headerVendor) {
            return $headerVendor;
        }

        return 'public';
    }
}
