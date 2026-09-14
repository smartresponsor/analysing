<?php

declare(strict_types=1);

namespace App\Analysing\Service\Http;

use App\Analysing\ServiceInterface\Http\AnalyticsVendorContextInterface;

final class AnalyticsVendorContext implements AnalyticsVendorContextInterface
{
    private string $vendor = 'public';

    public function set(string $vendor): void
    {
        $normalized = trim($vendor);
        $this->vendor = '' !== $normalized ? $normalized : 'public';
    }

    public function current(): string
    {
        return $this->vendor;
    }
}
