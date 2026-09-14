<?php

declare(strict_types=1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->add('analytics_status', '/status')
        ->controller([App\Analysing\Controller\AnalyticsController::class, 'status'])
        ->methods(['GET']);

    $routes->add('analytics_funnel', '/analytics/funnel')
        ->controller([App\Analysing\Controller\AnalyticsController::class, 'funnel'])
        ->methods(['POST']);

    $routes->add('analytics_retention', '/analytics/retention')
        ->controller([App\Analysing\Controller\AnalyticsController::class, 'retention'])
        ->methods(['POST']);

    $routes->add('analytics_cohort', '/analytics/cohort')
        ->controller([App\Analysing\Controller\AnalyticsController::class, 'cohort'])
        ->methods(['POST']);

    $routes->add('analytics_health', '/analytics/health')
        ->controller([App\Analysing\Controller\AnalyticsHealthController::class, 'ping'])
        ->methods(['GET']);

    $routes->add('analytics_metrics', '/analytics/metrics')
        ->controller([App\Analysing\Controller\AnalyticsApiController::class, 'metrics'])
        ->methods(['GET']);

    $routes->add('analytics_dashboard_kpi', '/analytics/dashboard/kpi')
        ->controller([App\Analysing\Controller\AnalyticsDashboardController::class, 'kpi'])
        ->methods(['GET']);

    $routes->add('analytics_dashboard_timeseries', '/analytics/dashboard/timeseries')
        ->controller([App\Analysing\Controller\AnalyticsDashboardController::class, 'timeseries'])
        ->methods(['GET']);

    $routes->add('analytics_dashboard_top_vendors', '/analytics/dashboard/top/vendors')
        ->controller([App\Analysing\Controller\AnalyticsDashboardController::class, 'topVendors'])
        ->methods(['GET']);

    $routes->add('analytics_dashboard_page', '/analytics/dashboard')
        ->controller([App\Analysing\Controller\AnalyticsDashboardPageController::class, 'index'])
        ->methods(['GET']);

    $routes->add('analytics_aggregate_funnel', '/analytics/aggregate/funnel')
        ->controller([App\Analysing\Controller\AnalyticsAggregateController::class, 'funnel'])
        ->methods(['POST']);

    $routes->add('analytics_aggregate_retention', '/analytics/aggregate/retention')
        ->controller([App\Analysing\Controller\AnalyticsAggregateController::class, 'retention'])
        ->methods(['POST']);

    $routes->add('analytics_aggregate_path', '/analytics/aggregate/path')
        ->controller([App\Analysing\Controller\AnalyticsAggregateController::class, 'path'])
        ->methods(['POST']);

    $routes->add('analytics_ingest_rudder', '/analytics/ingest/rudder')
        ->controller([App\Analysing\Controller\AnalyticsIngestController::class, 'ingestRudder'])
        ->methods(['POST']);

    $routes->add('analytics_ingest_segment', '/analytics/ingest/segment')
        ->controller([App\Analysing\Controller\AnalyticsIngestController::class, 'ingestSegment'])
        ->methods(['POST']);

    $routes->add('analytics_experiment_allocate', '/analytics/experiment/allocate')
        ->controller([App\Analysing\Controller\AnalyticsExperimentController::class, 'allocate'])
        ->methods(['POST']);

    $routes->add('analytics_flag_evaluate', '/analytics/flag/evaluate')
        ->controller([App\Analysing\Controller\AnalyticsFlagController::class, 'evaluate'])
        ->methods(['POST']);

    $routes->add('analytics_insight_anomaly', '/analytics/insight/anomaly')
        ->controller([App\Analysing\Controller\AnalyticsInsightController::class, 'anomaly'])
        ->methods(['POST']);

    $routes->add('analytics_insight_metric_tree', '/analytics/insight/metric/tree')
        ->controller([App\Analysing\Controller\AnalyticsInsightController::class, 'metricTree'])
        ->methods(['POST']);
};
