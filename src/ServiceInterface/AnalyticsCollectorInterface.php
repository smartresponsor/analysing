<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface;

interface AnalyticsCollectorInterface
{
    /**
     * @param array<string, scalar|null>|null $dimensions
     */
    public function record(
        string $metric,
        float $value,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
        ?array $dimensions = null,
    ): void;
}
