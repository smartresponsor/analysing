<?php

declare(strict_types=1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->add('analytics_status', '/status')
        ->controller([App\Controller\Analytics\AnalyticsController::class, 'status'])
        ->methods(['GET']);

    $routes->add('analytics_funnel', '/analytics/funnel')
        ->controller([App\Controller\Analytics\AnalyticsController::class, 'funnel'])
        ->methods(['POST']);

    $routes->add('analytics_retention', '/analytics/retention')
        ->controller([App\Controller\Analytics\AnalyticsController::class, 'retention'])
        ->methods(['POST']);

    $routes->add('analytics_cohort', '/analytics/cohort')
        ->controller([App\Controller\Analytics\AnalyticsController::class, 'cohort'])
        ->methods(['POST']);

    $routes->add('analytics_health', '/analytics/health')
        ->controller([App\Controller\Analytics\HealthController::class, 'ping'])
        ->methods(['GET']);

    $routes->add('analytics_metrics', '/analytics/metrics')
        ->controller([App\Controller\Analytics\ApiController::class, 'metrics'])
        ->methods(['GET']);

    $routes->add('analytics_dashboard_kpi', '/analytics/dashboard/kpi')
        ->controller([App\Controller\Analytics\DashboardController::class, 'kpi'])
        ->methods(['GET']);

    $routes->add('analytics_dashboard_timeseries', '/analytics/dashboard/timeseries')
        ->controller([App\Controller\Analytics\DashboardController::class, 'timeseries'])
        ->methods(['GET']);

    $routes->add('analytics_dashboard_top_vendors', '/analytics/dashboard/top-vendors')
        ->controller([App\Controller\Analytics\DashboardController::class, 'topVendors'])
        ->methods(['GET']);

    $routes->add('analytics_dashboard_page', '/analytics/dashboard')
        ->controller([App\Controller\Analytics\DashboardPageController::class, 'index'])
        ->methods(['GET']);

    $routes->add('analytics_aggregate_funnel', '/analytics/aggregate/funnel')
        ->controller([App\Controller\Analytics\AggregateController::class, 'funnel'])
        ->methods(['POST']);

    $routes->add('analytics_aggregate_retention', '/analytics/aggregate/retention')
        ->controller([App\Controller\Analytics\AggregateController::class, 'retention'])
        ->methods(['POST']);

    $routes->add('analytics_aggregate_path', '/analytics/aggregate/path')
        ->controller([App\Controller\Analytics\AggregateController::class, 'path'])
        ->methods(['POST']);

    $routes->add('analytics_ingest_rudder', '/analytics/ingest/rudder')
        ->controller([App\Controller\Analytics\IngestController::class, 'ingestRudder'])
        ->methods(['POST']);

    $routes->add('analytics_ingest_segment', '/analytics/ingest/segment')
        ->controller([App\Controller\Analytics\IngestController::class, 'ingestSegment'])
        ->methods(['POST']);

    $routes->add('analytics_experiment_allocate', '/analytics/experiment/allocate')
        ->controller([App\Controller\Analytics\ExperimentController::class, 'allocate'])
        ->methods(['POST']);

    $routes->add('analytics_flag_evaluate', '/analytics/flag/evaluate')
        ->controller([App\Controller\Analytics\FlagController::class, 'evaluate'])
        ->methods(['POST']);

    $routes->add('analytics_insight_anomaly', '/analytics/insight/anomaly')
        ->controller([App\Controller\Analytics\InsightController::class, 'anomaly'])
        ->methods(['POST']);

    $routes->add('analytics_insight_metric_tree', '/analytics/insight/metric-tree')
        ->controller([App\Controller\Analytics\InsightController::class, 'metricTree'])
        ->methods(['POST']);
};
