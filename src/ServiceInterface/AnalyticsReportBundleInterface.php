<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface;

interface AnalyticsReportBundleInterface
{
    /**
     * @param array<string, list<array<string,mixed>>> $datasets
     *
     * @return array<string, array{rows:int,columns:list<string>}>
     */
    public function pack(array $datasets): array;
}
