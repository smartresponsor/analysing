<?php

declare(strict_types=1);

namespace App\DomainInterface\Analytics;

interface AnalyticsInterface
{
    /**
     * @param array<string,mixed> $param
     *
     * @return list<array<string,mixed>>
     */
    public function runFunnel(array $param): array;

    /**
     * @param array<string,mixed> $param
     *
     * @return list<array<string,mixed>>
     */
    public function runRetention(array $param): array;

    /**
     * @param array<string,mixed> $param
     *
     * @return list<array<string,mixed>>
     */
    public function runCohort(array $param): array;
}
