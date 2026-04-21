<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface\Analytics;

use App\Analysing\ValueObject\Analytics\TenantId;

interface TenantScopeInterface
{
    /**
     * @param list<array<string,mixed>> $rows
     *
     * @return list<array<string,mixed>>
     */
    public function filter(array $rows, TenantId $tenant): array;
}
