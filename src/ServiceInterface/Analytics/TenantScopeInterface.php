<?php

declare(strict_types=1);

namespace App\ServiceInterface\Analytics;

use App\ValueObject\Analytics\TenantId;

interface TenantScopeInterface
{
    /**
     * @param list<array<string,mixed>> $rows
     *
     * @return list<array<string,mixed>>
     */
    public function filter(array $rows, TenantId $tenant): array;
}
