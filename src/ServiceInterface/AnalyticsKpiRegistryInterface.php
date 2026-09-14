<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface;

use App\Analysing\ValueObject\AnalyticsKpiId;

interface AnalyticsKpiRegistryInterface
{
    /**
     * @return array<string,string>
     */
    public function list(): array;

    public function has(AnalyticsKpiId $id): bool;
}
