<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface;

use App\Analysing\ValueObject\AnalyticsVendorId;

interface AnalyticsVendorScopeInterface
{
    /**
     * @param list<array<string,mixed>> $rows
     *
     * @return list<array<string,mixed>>
     */
    public function filter(array $rows, AnalyticsVendorId $vendor): array;
}
