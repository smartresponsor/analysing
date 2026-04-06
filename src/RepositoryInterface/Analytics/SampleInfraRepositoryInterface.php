<?php

declare(strict_types=1);

namespace App\RepositoryInterface\Analytics;

interface SampleInfraRepositoryInterface extends InfraRepositoryInterface
{
    /**
     * @param list<string> $steps
     *
     * @return list<array<string,mixed>>
     */
    public function fetchFunnel(string $app, string $env, array $steps, \DateTimeImmutable $from, \DateTimeImmutable $to): array;

    /** @return list<array<string,mixed>> */
    public function fetchRetention(string $app, string $env, \DateTimeImmutable $cohort, int $days): array;

    /** @return list<array<string,mixed>> */
    public function fetchPath(string $app, string $env, \DateTimeImmutable $day, int $top): array;

    public function upsertExperimentMetricDaily(
        string $experimentKey,
        string $variantKey,
        \DateTimeImmutable $day,
        int $exposure,
        int $conversion,
        float $valueSum,
    ): void;
}
