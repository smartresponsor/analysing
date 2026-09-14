<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface;

interface AnalyticsExperimentServiceInterface
{
    public function choose(string $experimentKey, string $subjectId): string;

    public function record(string $experimentKey, string $variantKey, string $metric, float $value = 1.0): void;
}
