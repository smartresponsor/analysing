<?php

declare(strict_types=1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->add('analytics_status', '/status')
        ->controller([App\Analysing\Controller\Analytics\AnalyticsController::class, 'status'])
        ->methods(['GET']);

    $routes->add('analytics_funnel', '/analytics/funnel')
        ->controller([App\Analysing\Controller\Analytics\AnalyticsController::class, 'funnel'])
        ->methods(['POST']);

    $routes->add('analytics_retention', '/analytics/retention')
        ->controller([App\Analysing\Controller\Analytics\AnalyticsController::class, 'retention'])
        ->methods(['POST']);

    $routes->add('analytics_cohort', '/analytics/cohort')
        ->controller([App\Analysing\Controller\Analytics\AnalyticsController::class, 'cohort'])
        ->methods(['POST']);

    $routes->add('analytics_health', '/analytics/health')
        ->controller([App\Analysing\Controller\Analytics\HealthController::class, 'ping'])
        ->methods(['GET']);

    $routes->add('analytics_metrics', '/analytics/metrics')
        ->controller([App\Analysing\Controller\Analytics\ApiController::class, 'metrics'])
        ->methods(['GET']);

    $routes->add('analytics_dashboard_kpi', '/analytics/dashboard/kpi')
        ->controller([App\Analysing\Controller\Analytics\DashboardController::class, 'kpi'])
        ->methods(['GET']);

    $routes->add('analytics_dashboard_timeseries', '/analytics/dashboard/timeseries')
        ->controller([App\Analysing\Controller\Analytics\DashboardController::class, 'timeseries'])
        ->methods(['GET']);

    $routes->add('analytics_dashboard_top_vendors', '/analytics/dashboard/top-vendors')
        ->controller([App\Analysing\Controller\Analytics\DashboardController::class, 'topVendors'])
        ->methods(['GET']);

    $routes->add('analytics_dashboard_page', '/analytics/dashboard')
        ->controller([App\Analysing\Controller\Analytics\DashboardPageController::class, 'index'])
        ->methods(['GET']);

    $routes->add('analytics_aggregate_funnel', '/analytics/aggregate/funnel')
        ->controller([App\Analysing\Controller\Analytics\AggregateController::class, 'funnel'])
        ->methods(['POST']);

    $routes->add('analytics_aggregate_retention', '/analytics/aggregate/retention')
        ->controller([App\Analysing\Controller\Analytics\AggregateController::class, 'retention'])
        ->methods(['POST']);

    $routes->add('analytics_aggregate_path', '/analytics/aggregate/path')
        ->controller([App\Analysing\Controller\Analytics\AggregateController::class, 'path'])
        ->methods(['POST']);

    $routes->add('analytics_ingest_rudder', '/analytics/ingest/rudder')
        ->controller([App\Analysing\Controller\Analytics\IngestController::class, 'ingestRudder'])
        ->methods(['POST']);

    $routes->add('analytics_ingest_segment', '/analytics/ingest/segment')
        ->controller([App\Analysing\Controller\Analytics\IngestController::class, 'ingestSegment'])
        ->methods(['POST']);

    $routes->add('analytics_experiment_allocate', '/analytics/experiment/allocate')
        ->controller([App\Analysing\Controller\Analytics\ExperimentController::class, 'allocate'])
        ->methods(['POST']);

    $routes->add('analytics_flag_evaluate', '/analytics/flag/evaluate')
        ->controller([App\Analysing\Controller\Analytics\FlagController::class, 'evaluate'])
        ->methods(['POST']);

    $routes->add('analytics_insight_anomaly', '/analytics/insight/anomaly')
        ->controller([App\Analysing\Controller\Analytics\InsightController::class, 'anomaly'])
        ->methods(['POST']);

    $routes->add('analytics_insight_metric_tree', '/analytics/insight/metric-tree')
        ->controller([App\Analysing\Controller\Analytics\InsightController::class, 'metricTree'])
        ->methods(['POST']);
};
