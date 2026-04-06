<?php

declare(strict_types=1);

namespace App\ServiceInterface\Http;

use Symfony\Component\HttpFoundation\Request;

interface RequestCorrelationIdProviderInterface
{
    public function initialize(Request $request): string;

    public function current(): string;
}
