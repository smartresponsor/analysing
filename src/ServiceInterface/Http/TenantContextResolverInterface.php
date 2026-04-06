<?php

declare(strict_types=1);

namespace App\ServiceInterface\Http;

use Symfony\Component\HttpFoundation\Request;

interface TenantContextResolverInterface
{
    public function resolve(Request $request): string;
}
