<?php

declare(strict_types=1);

namespace App\ServiceInterface\Analytics;

interface ReportBundleInterface
{
    /**
     * @param array<string, list<array<string,mixed>>> $datasets
     *
     * @return array{
     *   datasets: array<string, array{rows:int,columns:list<string>}>,
     *   dataset_count:int,
     *   row_count:int
     * }
     */
    public function pack(array $datasets): array;
}
