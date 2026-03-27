<?php

declare(strict_types=1);

namespace App\ServiceInterface\Analytics;

use App\ValueObject\Analytics\KpiId;

interface KpiRegistryInterface
{
    /**
     * @return array<string,string>
     */
    public function list(): array;

    public function has(KpiId $id): bool;
}
