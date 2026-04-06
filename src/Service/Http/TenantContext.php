<?php

declare(strict_types=1);

namespace App\Service\Http;

use App\ServiceInterface\Http\TenantContextInterface;

final class TenantContext implements TenantContextInterface
{
    private string $tenant = 'public';

    public function set(string $tenant): void
    {
        $normalized = trim($tenant);
        $this->tenant = '' !== $normalized ? $normalized : 'public';
    }

    public function current(): string
    {
        return $this->tenant;
    }
}
