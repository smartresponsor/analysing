<?php

declare(strict_types=1);

use App\Infrastructure\Doctrine\ConnectionFactory;
use App\Infrastructure\Doctrine\EntityManagerFactory;
use App\Repository\Analytics\SampleInfraRepository;
use App\Service\Analytics\HealthService;
use App\Service\Analytics\SampleDashboardService;
use App\Service\Http\AnalyticsIdempotencyStore;
use App\Service\Http\AnalyticsRouteRateLimiter;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $parameters = $container->parameters();
    $parameters->set('analytics.clickhouse.base', 'http://127.0.0.1:8123');
    $parameters->set('analytics.clickhouse.user', 'default');
    $parameters->set('analytics.clickhouse.pass', '');
    $parameters->set('analytics.runtime.salt', 'analytics-default-salt');

    $databaseUrl = getenv('ANALYTICS_DATABASE_URL');
    $hasExternalDatabase = is_string($databaseUrl) && '' !== trim($databaseUrl);
    $hasSqliteRuntime = extension_loaded('pdo') && extension_loaded('pdo_sqlite');
    $sampleRuntime = !$hasExternalDatabase && !$hasSqliteRuntime;

    $parameters->set('analytics.storage.driver', $hasExternalDatabase ? 'database_url' : ($hasSqliteRuntime ? 'pdo_sqlite' : 'embedded_sample'));
    $parameters->set('analytics.storage.mode', $sampleRuntime ? 'sample' : 'database');
    $parameters->set('analytics.storage.available', true);
    $parameters->set('analytics.storage.prepare_command', 'php bin/console analytics:storage:prepare --seed');
    $parameters->set('analytics.storage.required_tables', [
        'analytics_metric_snapshot',
        'analytics_export_job',
        'analytics_alert_rule',
        'aggregate_funnel_daily',
        'retention_cohort_daily',
        'path_transition_daily',
        'experiment_metric_daily',
    ]);

    $idempotencyEnabled = filter_var(getenv('ANALYTICS_IDEMPOTENCY_ENABLED') ?: '1', FILTER_VALIDATE_BOOL);
    $idempotencyRequired = filter_var(getenv('ANALYTICS_IDEMPOTENCY_REQUIRED') ?: '0', FILTER_VALIDATE_BOOL);
    $idempotencyTtl = (int) (getenv('ANALYTICS_IDEMPOTENCY_TTL_SECONDS') ?: 86400);
    $idempotencyDirectory = trim((string) (getenv('ANALYTICS_IDEMPOTENCY_DIRECTORY') ?: dirname(__DIR__).'/var/analytics_idempotency'));

    $parameters->set('analytics.idempotency.enabled', $idempotencyEnabled);
    $parameters->set('analytics.idempotency.required', $idempotencyRequired);
    $parameters->set('analytics.idempotency.mode', $idempotencyEnabled ? 'local_file' : 'disabled');
    $parameters->set('analytics.idempotency.ttl_seconds', max(60, $idempotencyTtl));
    $parameters->set('analytics.idempotency.directory', '' !== $idempotencyDirectory ? $idempotencyDirectory : dirname(__DIR__).'/var/analytics_idempotency');

    $authRequired = filter_var(getenv('ANALYTICS_AUTH_REQUIRED') ?: '0', FILTER_VALIDATE_BOOL);
    $authPublicRead = filter_var(getenv('ANALYTICS_AUTH_PUBLIC_READ') ?: '1', FILTER_VALIDATE_BOOL);

    $parameters->set('analytics.auth.required', $authRequired);
    $parameters->set('analytics.auth.public_read', $authPublicRead);

    $rateLimitEnabled = filter_var(getenv('ANALYTICS_RATE_LIMIT_ENABLED') ?: '1', FILTER_VALIDATE_BOOL);
    $rateLimitWindowSeconds = (int) (getenv('ANALYTICS_RATE_LIMIT_WINDOW_SECONDS') ?: 60);
    $rateLimitDefaultWriteLimit = (int) (getenv('ANALYTICS_RATE_LIMIT_DEFAULT_WRITE_LIMIT') ?: 60);
    $rateLimitDirectory = trim((string) (getenv('ANALYTICS_RATE_LIMIT_DIRECTORY') ?: dirname(__DIR__).'/var/analytics_rate_limit'));

    $parameters->set('analytics.rate_limit.enabled', $rateLimitEnabled);
    $parameters->set('analytics.rate_limit.mode', $rateLimitEnabled ? 'local_file' : 'disabled');
    $parameters->set('analytics.rate_limit.window_seconds', max(1, $rateLimitWindowSeconds));
    $parameters->set('analytics.rate_limit.default_write_limit', max(1, $rateLimitDefaultWriteLimit));
    $parameters->set('analytics.rate_limit.directory', '' !== $rateLimitDirectory ? $rateLimitDirectory : dirname(__DIR__).'/var/analytics_rate_limit');

    $services = $container->services();
    $services->defaults()
        ->autowire()
        ->autoconfigure()
        ->bind('string $base', param('analytics.clickhouse.base'))
        ->bind('string $user', param('analytics.clickhouse.user'))
        ->bind('string $pass', param('analytics.clickhouse.pass'))
        ->bind('string $salt', param('analytics.runtime.salt'));

    $services->load('App\\Controller\\', '../src/Controller/')
        ->tag('controller.service_arguments')
        ->public();

    $services->load('App\\Command\\', '../src/Command/');
    $services->load('App\\Domain\\', '../src/Domain/');
    $services->load('App\\Infrastructure\\', '../src/Infrastructure/');
    $services->load('App\\Repository\\', '../src/Repository/');
    $services->load('App\\Service\\', '../src/Service/');

    $services->set(ConnectionFactory::class);
    $services->set(Connection::class)
        ->factory([service(ConnectionFactory::class), 'create']);

    $services->set(EntityManagerFactory::class);
    $services->set(EntityManagerInterface::class)
        ->factory([service(EntityManagerFactory::class), 'create'])
        ->args([service(Connection::class)]);

    $services->alias('App\\ControllerInterface\\Analytics\\AggregateControllerInterface', 'App\\Controller\\Analytics\\AggregateController');
    $services->alias('App\\ControllerInterface\\Analytics\\AnalyticsControllerInterface', 'App\\Controller\\Analytics\\AnalyticsController');
    $services->alias('App\\ControllerInterface\\Analytics\\ApiControllerInterface', 'App\\Controller\\Analytics\\ApiController');
    $services->alias('App\\ControllerInterface\\Analytics\\DashboardControllerInterface', 'App\\Controller\\Analytics\\DashboardController');
    $services->alias('App\\ControllerInterface\\Analytics\\DashboardPageControllerInterface', 'App\\Controller\\Analytics\\DashboardPageController');
    $services->alias('App\\ControllerInterface\\Analytics\\ExperimentControllerInterface', 'App\\Controller\\Analytics\\ExperimentController');
    $services->alias('App\\ControllerInterface\\Analytics\\FlagControllerInterface', 'App\\Controller\\Analytics\\FlagController');
    $services->alias('App\\ControllerInterface\\Analytics\\HealthControllerInterface', 'App\\Controller\\Analytics\\HealthController');
    $services->alias('App\\ControllerInterface\\Analytics\\IngestControllerInterface', 'App\\Controller\\Analytics\\IngestController');
    $services->alias('App\\ControllerInterface\\Analytics\\InsightControllerInterface', 'App\\Controller\\Analytics\\InsightController');

    $services->alias('App\\DomainInterface\\Analytics\\AnalyticsInterface', 'App\\Domain\\Analytics\\Analytics');
    $services->alias('App\\DomainInterface\\Analytics\\ExperimentInterface', 'App\\Domain\\Analytics\\Experiment');
    $services->alias('App\\DomainInterface\\Analytics\\FlagInterface', 'App\\Domain\\Analytics\\Flag');
    $services->alias('App\\DomainInterface\\Analytics\\InsightInterface', 'App\\Domain\\Analytics\\Insight');
    $services->alias('App\\DomainInterface\\Analytics\\ClickhouseClientInterface', 'App\\Domain\\Analytics\\ClickhouseClient');

    $services->alias('App\RepositoryInterface\Analytics\InfraRepositoryInterface', $sampleRuntime ? SampleInfraRepository::class : 'App\Repository\Analytics\InfraRepository');

    $services->alias('App\\ServiceInterface\\Alerts\\AlertEvaluatorInterface', 'App\\Service\\Alerts\\AlertEvaluator');
    $services->alias('App\\ServiceInterface\\Alerts\\NotificationDispatcherInterface', 'App\\Service\\Alerts\\NotificationDispatcher');
    $services->alias('App\\ServiceInterface\\Analytics\\AnalyticsCollectorInterface', 'App\\Service\\Analytics\\AnalyticsCollector');
    $services->alias('App\\ServiceInterface\\Analytics\\ExperimentServiceInterface', 'App\\Service\\Analytics\\ExperimentService');
    $services->alias('App\\ServiceInterface\\Analytics\\MetricIngestServiceInterface', 'App\\Service\\Analytics\\MetricIngestService');
    $services->alias('App\\ServiceInterface\\Analytics\\RollupServiceInterface', 'App\\Service\\Analytics\\RollupService');
    $services->alias('App\\ServiceInterface\\Analytics\\SegmentationServiceInterface', 'App\\Service\\Analytics\\SegmentationService');
    $services->alias('App\\ServiceInterface\\Analytics\\CsvImporterInterface', 'App\\Service\\Analytics\\CsvImporter');
    $services->alias('App\\ServiceInterface\\Analytics\\FileNotifierInterface', 'App\\Service\\Analytics\\FileNotifier');
    $services->alias('App\\ServiceInterface\\Analytics\\GzipWriterInterface', 'App\\Service\\Analytics\\GzipWriter');
    $services->alias('App\\ServiceInterface\\Analytics\\LocalCacheInterface', 'App\\Service\\Analytics\\LocalCache');
    $services->alias('App\\ServiceInterface\\Analytics\\WebhookNotifierInterface', 'App\\Service\\Analytics\\WebhookNotifier');
    $services->alias('App\\ServiceInterface\\Analytics\\AsyncQueryServiceInterface', 'App\\Service\\Analytics\\AsyncQueryService');
    $services->alias('App\\ServiceInterface\\Analytics\\BackfillServiceInterface', 'App\\Service\\Analytics\\BackfillService');
    $services->alias('App\\ServiceInterface\\Analytics\\ReportExporterServiceInterface', 'App\\Service\\Analytics\\ReportExporterService');
    $services->alias('App\\ServiceInterface\\Analytics\\ReportGeneratorServiceInterface', 'App\\Service\\Analytics\\ReportGeneratorService');
    $services->alias('App\\ServiceInterface\\Analytics\\RetentionServiceInterface', 'App\\Service\\Analytics\\RetentionService');
    $services->alias('App\\ServiceInterface\\Analytics\\TokenServiceInterface', 'App\\Service\\Analytics\\TokenService');
    $services->alias('App\\ServiceInterface\\Analytics\\AccessGuardInterface', 'App\\Service\\Analytics\\AccessGuard');
    $services->alias('App\\ServiceInterface\\Analytics\\AggregateServiceInterface', 'App\\Service\\Analytics\\AggregateService');
    $services->alias('App\\ServiceInterface\\Analytics\\AnomalyDetectorInterface', 'App\\Service\\Analytics\\AnomalyDetector');
    $services->alias('App\\ServiceInterface\\Analytics\\CacheInterface', 'App\\Service\\Analytics\\LocalCache');
    $services->alias('App\\ServiceInterface\\Analytics\\CsvImportInterface', 'App\\Service\\Analytics\\CsvImporter');
    $services->alias('App\ServiceInterface\Analytics\DashboardServiceInterface', $sampleRuntime ? SampleDashboardService::class : 'App\Service\Analytics\DashboardService');
    $services->alias('App\\ServiceInterface\\Analytics\\HealthServiceInterface', 'App\\Service\\Analytics\\HealthService');
    $services->alias('App\\ServiceInterface\\Analytics\\KpiRegistryInterface', 'App\\Service\\Analytics\\KpiRegistry');
    $services->alias('App\\ServiceInterface\\Analytics\\MetricIngestInterface', 'App\\Service\\Analytics\\MetricIngestService');
    $services->alias('App\\ServiceInterface\\Analytics\\NotifierInterface', 'App\\Service\\Analytics\\WebhookNotifier');
    $services->alias('App\\ServiceInterface\\Analytics\\ReportBundleInterface', 'App\\Service\\Analytics\\ReportBundle');
    $services->alias('App\\ServiceInterface\\Analytics\\RetentionInterface', 'App\\Service\\Analytics\\RetentionService');
    $services->alias('App\\ServiceInterface\\Analytics\\RollupInterface', 'App\\Service\\Analytics\\RollupService');
    $services->alias('App\\ServiceInterface\\Analytics\\SegmentationInterface', 'App\\Service\\Analytics\\SegmentationService');
    $services->alias('App\\ServiceInterface\\Analytics\\SloCalculatorInterface', 'App\\Service\\Analytics\\SloCalculator');
    $services->alias('App\\ServiceInterface\\Analytics\\TenantScopeInterface', 'App\\Service\\Analytics\\TenantScope');
    $services->alias('App\\ServiceInterface\\Analytics\\TransformerInterface', 'App\\Service\\Analytics\\Transformer');
    $services->alias('App\\ServiceInterface\\Analytics\\WindowQueryInterface', 'App\\Service\\Analytics\\WindowQuery');


    $services->alias('App\RepositoryInterface\Analytics\SampleInfraRepositoryInterface', 'App\Repository\Analytics\SampleInfraRepository');
    $services->alias('App\ServiceInterface\Analytics\DashboardHtmlRendererInterface', 'App\Service\Analytics\DashboardHtmlRenderer');
    $services->alias('App\ServiceInterface\Analytics\SampleAnalyticsDatasetInterface', 'App\Service\Analytics\SampleAnalyticsDataset');
    $services->alias('App\ServiceInterface\Analytics\SampleDashboardServiceInterface', 'App\Service\Analytics\SampleDashboardService');
    $services->alias('App\ServiceInterface\Http\AnalyticsErrorResponseFactoryInterface', 'App\Service\Http\AnalyticsErrorResponseFactory');
    $services->alias('App\ServiceInterface\Http\AnalyticsIdempotencyRequestSubscriberInterface', 'App\Service\Http\AnalyticsIdempotencyRequestSubscriber');
    $services->alias('App\ServiceInterface\Http\AnalyticsIdempotencyResponseSubscriberInterface', 'App\Service\Http\AnalyticsIdempotencyResponseSubscriber');
    $services->alias('App\ServiceInterface\Http\AnalyticsIdempotencyStoreInterface', 'App\Service\Http\AnalyticsIdempotencyStore');
    $services->alias('App\ServiceInterface\Http\AnalyticsRateLimitResponseSubscriberInterface', 'App\Service\Http\AnalyticsRateLimitResponseSubscriber');
    $services->alias('App\ServiceInterface\Http\AnalyticsRequestAuthSubscriberInterface', 'App\Service\Http\AnalyticsRequestAuthSubscriber');
    $services->alias('App\ServiceInterface\Http\AnalyticsRouteRateLimiterInterface', 'App\Service\Http\AnalyticsRouteRateLimiter');
    $services->alias('App\ServiceInterface\Http\AnalyticsSuccessResponseFactoryInterface', 'App\Service\Http\AnalyticsSuccessResponseFactory');
    $services->alias('App\ServiceInterface\Http\AnalyticsWriteRateLimitSubscriberInterface', 'App\Service\Http\AnalyticsWriteRateLimitSubscriber');
    $services->alias('App\ServiceInterface\Http\RequestCorrelationIdProviderInterface', 'App\Service\Http\RequestCorrelationIdProvider');
    $services->alias('App\ServiceInterface\Http\RequestCorrelationIdSubscriberInterface', 'App\Service\Http\RequestCorrelationIdSubscriber');
    $services->alias('App\ServiceInterface\Http\TenantContextInterface', 'App\Service\Http\TenantContext');
    $services->alias('App\ServiceInterface\Http\TenantContextResolverInterface', 'App\Service\Http\TenantContextResolver');
    $services->alias('App\ServiceInterface\Http\TenantContextResponseSubscriberInterface', 'App\Service\Http\TenantContextResponseSubscriber');
    $services->alias('App\ServiceInterface\Http\TenantContextSubscriberInterface', 'App\Service\Http\TenantContextSubscriber');


    $services->set(AnalyticsIdempotencyStore::class)
        ->arg('$directory', param('analytics.idempotency.directory'))
        ->arg('$ttlSeconds', param('analytics.idempotency.ttl_seconds'));

    $services->set(AnalyticsRouteRateLimiter::class)
        ->arg('$directory', param('analytics.rate_limit.directory'))
        ->arg('$windowSeconds', param('analytics.rate_limit.window_seconds'))
        ->arg('$defaultWriteLimit', param('analytics.rate_limit.default_write_limit'))
        ->arg('$enabled', param('analytics.rate_limit.enabled'));

    $services->set(HealthService::class)
        ->arg('$storageDriver', param('analytics.storage.driver'))
        ->arg('$storageMode', param('analytics.storage.mode'))
        ->arg('$storageAvailable', param('analytics.storage.available'))
        ->arg('$idempotencyEnabled', param('analytics.idempotency.enabled'))
        ->arg('$idempotencyMode', param('analytics.idempotency.mode'))
        ->arg('$idempotencyRequired', param('analytics.idempotency.required'))
        ->arg('$authRequired', param('analytics.auth.required'))
        ->arg('$authPublicRead', param('analytics.auth.public_read'))
        ->arg('$rateLimitEnabled', param('analytics.rate_limit.enabled'))
        ->arg('$rateLimitMode', param('analytics.rate_limit.mode'))
        ->arg('$rateLimitWindowSeconds', param('analytics.rate_limit.window_seconds'))
        ->arg('$rateLimitDefaultWriteLimit', param('analytics.rate_limit.default_write_limit'))
        ->arg('$storagePrepareCommand', param('analytics.storage.prepare_command'))
        ->arg('$storageRequiredTables', param('analytics.storage.required_tables'));

    $services->set('App\Service\Http\AnalyticsRequestAuthSubscriber')
        ->arg('$required', param('analytics.auth.required'))
        ->arg('$publicRead', param('analytics.auth.public_read'));

    $services->set('App\Service\Http\AnalyticsIdempotencyRequestSubscriber')
        ->arg('$enabled', param('analytics.idempotency.enabled'))
        ->arg('$required', param('analytics.idempotency.required'));

    $services->set('App\Service\Http\AnalyticsIdempotencyResponseSubscriber')
        ->arg('$enabled', param('analytics.idempotency.enabled'));

    $services->set('App\Service\Http\AnalyticsWriteRateLimitSubscriber');

    $services->set('App\Service\Http\AnalyticsRateLimitResponseSubscriber');

    $services->set(NullLogger::class);
    $services->alias(LoggerInterface::class, NullLogger::class);
};
