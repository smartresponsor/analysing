<?php

declare(strict_types=1);

namespace App\Analysing\RepositoryInterface\Analytics;

interface InfraRepositoryInterface
{
    /**
     * @param list<string> $steps
     *
     * @return list<array{day:mixed,user_count:mixed}>
     */
    public function fetchFunnel(string $app, string $env, array $steps, \DateTimeImmutable $from, \DateTimeImmutable $to): array;

    /**
     * @return list<array{day_offset:mixed,active_user:mixed}>
     */
    public function fetchRetention(string $app, string $env, \DateTimeImmutable $cohort, int $days): array;

    /**
     * @return list<array{from_event:mixed,to_event:mixed,transition_count:mixed}>
     */
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
