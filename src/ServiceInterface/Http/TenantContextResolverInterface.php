<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface\Http;

use Symfony\Component\HttpFoundation\Request;

interface TenantContextResolverInterface
{
    public function resolve(Request $request): string;
}
