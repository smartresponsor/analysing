<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface\Http;

interface AnalyticsVendorContextInterface
{
    public function set(string $vendor): void;

    public function current(): string;
}
