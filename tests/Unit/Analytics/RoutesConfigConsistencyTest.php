<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use PHPUnit\Framework\TestCase;

final class RoutesConfigConsistencyTest extends TestCase
{
    public function testRoutesYamlContainsLiveAnalyticsEndpoints(): void
    {
        $yaml = (string) file_get_contents(__DIR__.'/../../../config/routes.yaml');

        $expectedRoutes = [
            'analytics_funnel:' => '/analytics/funnel',
            'analytics_retention:' => '/analytics/retention',
            'analytics_cohort:' => '/analytics/cohort',
            'analytics_dashboard_kpi:' => '/analytics/dashboard/kpi',
            'analytics_aggregate_path:' => '/analytics/aggregate/path',
            'analytics_ingest_segment:' => '/analytics/ingest/segment',
            'analytics_experiment_allocate:' => '/analytics/experiment/allocate',
            'analytics_flag_evaluate:' => '/analytics/flag/evaluate',
            'analytics_insight_metric_tree:' => '/analytics/insight/metric-tree',
        ];

        foreach ($expectedRoutes as $routeName => $path) {
            self::assertStringContainsString($routeName, $yaml);
            self::assertStringContainsString($path, $yaml);
        }
    }
}
