<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface\Analytics;

interface BackfillServiceInterface
{
    /**
     * @return int minutes in the accepted backfill window
     */
    public function run(int $fromTs, int $toTs): int;
}
