<?php

declare(strict_types=1);

namespace App\Repository\Analytics;

use App\RepositoryInterface\Analytics\InfraRepositoryInterface;
use App\Service\Analytics\SampleAnalyticsDataset;
use Psr\Log\LoggerInterface;

final class SampleInfraRepository implements InfraRepositoryInterface
{
    public function __construct(
        private readonly SampleAnalyticsDataset $dataset,
        private readonly LoggerInterface $logger,
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

    public function upsertExperimentMetricDaily(
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
