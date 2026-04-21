<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface\Http;

interface TenantContextInterface
{
    public function set(string $tenant): void;

    public function current(): string;
}
