<?php

declare(strict_types=1);

namespace App\Analysing\ProviderInterface\Http;

use Symfony\Component\HttpFoundation\Request;

interface AnalyticsRequestCorrelationIdProviderInterface
{
    public function initialize(Request $request): string;

    public function current(): string;
}
