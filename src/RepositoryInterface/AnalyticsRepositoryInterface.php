<?php

declare(strict_types=1);

namespace App\Analysing\RepositoryInterface;

interface AnalyticsRepositoryInterface
{
    /**
     * @param list<string> $steps
     *
     * @return list<array{day:string,user_count:int}>
     */
    public function fetchFunnel(string $app, string $env, array $steps, \DateTimeImmutable $from, \DateTimeImmutable $to): array;

    /**
     * @return list<array{day_offset:int,active_user:int}>
     */
    public function fetchRetention(string $app, string $env, \DateTimeImmutable $cohort, int $days): array;

    /**
     * @param list<string> $steps
     *
     * @return list<array{cohort_date:string,user_count:int}>
     */
    public function fetchCohort(string $app, string $env, array $steps, \DateTimeImmutable $from, \DateTimeImmutable $to): array;

    /**
     * @return list<array{from_event:string,to_event:string,transition_count:int}>
     */
    public function fetchPath(string $app, string $env, \DateTimeImmutable $day, int $top): array;

    /**
     * @return list<array{user_count:int}>
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
