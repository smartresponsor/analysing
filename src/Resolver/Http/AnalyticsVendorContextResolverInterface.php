<?php

declare(strict_types=1);

namespace App\Analysing\Resolver\Http;

use Symfony\Component\HttpFoundation\Request;

interface AnalyticsVendorContextResolverInterface
{
    public function resolve(Request $request): string;
}
