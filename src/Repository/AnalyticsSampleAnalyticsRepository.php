<?php

declare(strict_types=1);

namespace App\Analysing\Repository;

use App\Analysing\RepositoryInterface\AnalyticsSampleAnalyticsRepositoryInterface;
use App\Analysing\ServiceInterface\AnalyticsSampleAnalyticsDatasetInterface;
use Psr\Log\LoggerInterface;

final readonly class AnalyticsSampleAnalyticsRepository implements AnalyticsSampleAnalyticsRepositoryInterface
{
    public function __construct(
        private AnalyticsSampleAnalyticsDatasetInterface $dataset,
        private LoggerInterface $logger,
    ) {
    }

    public function fetchFunnel(string $app, string $env, array $steps, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $rows = $this->dataset->funnel($app, $env, $steps, $from, $to);
        $this->logger->info('Analytics sample funnel data loaded.', [
            'app' => $app,
            'env' => $env,
            'rows' => count($rows),
        ]);

        return $rows;
    }

    public function fetchRetention(string $app, string $env, \DateTimeImmutable $cohort, int $days): array
    {
        $rows = $this->dataset->retention($app, $env, $cohort, $days);
        $this->logger->info('Analytics sample retention data loaded.', [
            'app' => $app,
            'env' => $env,
            'rows' => count($rows),
        ]);

        return $rows;
    }

    public function fetchPath(string $app, string $env, \DateTimeImmutable $day, int $top): array
    {
        $rows = $this->dataset->path($app, $env, $day, $top);
        $this->logger->info('Analytics sample path data loaded.', [
            'app' => $app,
            'env' => $env,
            'rows' => count($rows),
        ]);

        return $rows;
    }

    public function fetchCohort(string $app, string $env, array $steps, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $this->logger->info('Analytics sample repository returned an empty cohort series.', [
            'app' => $app,
            'env' => $env,
            'steps' => count($steps),
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
        ]);

        return [];
    }

    public function fetchAnomalySeries(string $metric, int $days): array
    {
        $this->logger->info('Analytics sample repository returned an empty anomaly series.', [
            'metric' => $metric,
            'days' => $days,
        ]);

        return [];
    }

    public function fetchLatestMetricValue(string $metric): int
    {
        $this->logger->info('Analytics sample repository returned a zero latest metric value.', [
            'metric' => $metric,
        ]);

        return 0;
    }

    public function upsertAnalyticsExperimentMetricDailyEntity(
        string $experimentKey,
        string $variantKey,
        \DateTimeImmutable $day,
        int $exposure,
        int $conversion,
        float $valueSum,
    ): void {
        $this->logger->info('Analytics sample repository ignored experiment metric upsert.', [
            'experiment_key' => $experimentKey,
            'variant_key' => $variantKey,
            'day' => $day->format('Y-m-d'),
            'exposure' => $exposure,
            'conversion' => $conversion,
            'value_sum' => $valueSum,
        ]);
    }
}
