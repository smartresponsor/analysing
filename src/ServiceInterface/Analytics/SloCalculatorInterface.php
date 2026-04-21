<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface\Analytics;

interface SloCalculatorInterface
{
    /**
     * @param list<mixed> $values
     */
    public function availability(array $values): float;
}
