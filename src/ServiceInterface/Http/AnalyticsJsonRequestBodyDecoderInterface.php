<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface\Http;

use Symfony\Component\HttpFoundation\Request;

interface AnalyticsJsonRequestBodyDecoderInterface
{
    /** @return array<string,mixed> */
    public function decode(Request $request): array;
}
