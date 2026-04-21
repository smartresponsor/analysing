<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface\Analytics;

use App\Analysing\ValueObject\Analytics\KpiId;

interface KpiRegistryInterface
{
    /**
     * @return array<string,string>
     */
    public function list(): array;

    public function has(KpiId $id): bool;
}
