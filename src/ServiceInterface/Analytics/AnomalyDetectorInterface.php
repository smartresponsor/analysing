<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface\Analytics;

interface AnomalyDetectorInterface
{
    /**
     * @param list<mixed> $values
     *
     * @return list<float>
     */
    public function zscore(array $values): array;
}
