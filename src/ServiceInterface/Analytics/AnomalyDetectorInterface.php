<?php

declare(strict_types=1);

namespace App\ServiceInterface\Analytics;

interface AnomalyDetectorInterface
{
    /**
     * @param list<mixed> $values
     *
     * @return list<float>
     */
    public function zscore(array $values): array;
}
