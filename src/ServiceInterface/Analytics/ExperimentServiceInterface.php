<?php

declare(strict_types=1);

namespace App\ServiceInterface\Analytics;

interface ExperimentServiceInterface
{
    /**
     * @return non-empty-string
     */
    public function choose(string $experimentKey, string $subjectId): string;

    public function record(string $experimentKey, string $variantKey, string $metric, float $value = 1.0): void;
}
