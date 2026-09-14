<?php

declare(strict_types=1);

namespace App\Analysing\RepositoryInterface;

interface AnalyticsRepositoryInterface
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
     * @param list<string> $steps
     *
     * @return list<array{cohort_date:mixed,user_count:mixed}>
     */
    public function fetchCohort(string $app, string $env, array $steps, \DateTimeImmutable $from, \DateTimeImmutable $to): array;

    /**
     * @return list<array{from_event:mixed,to_event:mixed,transition_count:mixed}>
     */
    public function fetchPath(string $app, string $env, \DateTimeImmutable $day, int $top): array;

    /**
     * @return list<array{user_count:mixed}>
     */
    public function fetchAnomalySeries(string $metric, int $days): array;

    public function fetchLatestMetricValue(string $metric): int;

    public function upsertAnalyticsExperimentMetricDailyEntity(
        string $experimentKey,
        string $variantKey,
        \DateTimeImmutable $day,
        int $exposure,
        int $conversion,
        float $valueSum,
    ): void;
}
