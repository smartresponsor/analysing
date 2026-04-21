<?php

declare(strict_types=1);

use App\Analysing\Infrastructure\Doctrine\ConnectionFactory;
use App\Analysing\Infrastructure\Doctrine\EntityManagerFactory;
use App\Analysing\Repository\Analytics\SampleInfraRepository;
use App\Analysing\Service\Analytics\HealthService;
use App\Analysing\Service\Analytics\SampleDashboardService;
use App\Analysing\Service\Http\AnalyticsIdempotencyStore;
use App\Analysing\Service\Http\AnalyticsRouteRateLimiter;
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

    $services->load('App\Analysing\\Controller\\', '../src/Controller/')
        ->tag('controller.service_arguments')
        ->public();

    $services->load('App\Analysing\\Command\\', '../src/Command/');
    $services->load('App\Analysing\\Domain\\', '../src/Domain/');
    $services->load('App\Analysing\\Infrastructure\\', '../src/Infrastructure/');
    $services->load('App\Analysing\\Repository\\', '../src/Repository/');
    $services->load('App\Analysing\\Service\\', '../src/Service/');

    $services->set(ConnectionFactory::class);
    $services->set(Connection::class)
        ->factory([service(ConnectionFactory::class), 'create']);

    $services->set(EntityManagerFactory::class);
    $services->set(EntityManagerInterface::class)
        ->factory([service(EntityManagerFactory::class), 'create'])
        ->args([service(Connection::class)]);

    $services->alias('App\Analysing\\ControllerInterface\\Analytics\\AggregateControllerInterface', 'App\Analysing\\Controller\\Analytics\\AggregateController');
    $services->alias('App\Analysing\\ControllerInterface\\Analytics\\AnalyticsControllerInterface', 'App\Analysing\\Controller\\Analytics\\AnalyticsController');
    $services->alias('App\Analysing\\ControllerInterface\\Analytics\\ApiControllerInterface', 'App\Analysing\\Controller\\Analytics\\ApiController');
    $services->alias('App\Analysing\\ControllerInterface\\Analytics\\DashboardControllerInterface', 'App\Analysing\\Controller\\Analytics\\DashboardController');
    $services->alias('App\Analysing\\ControllerInterface\\Analytics\\DashboardPageControllerInterface', 'App\Analysing\\Controller\\Analytics\\DashboardPageController');
    $services->alias('App\Analysing\\ControllerInterface\\Analytics\\ExperimentControllerInterface', 'App\Analysing\\Controller\\Analytics\\ExperimentController');
    $services->alias('App\Analysing\\ControllerInterface\\Analytics\\FlagControllerInterface', 'App\Analysing\\Controller\\Analytics\\FlagController');
    $services->alias('App\Analysing\\ControllerInterface\\Analytics\\HealthControllerInterface', 'App\Analysing\\Controller\\Analytics\\HealthController');
    $services->alias('App\Analysing\\ControllerInterface\\Analytics\\IngestControllerInterface', 'App\Analysing\\Controller\\Analytics\\IngestController');
    $services->alias('App\Analysing\\ControllerInterface\\Analytics\\InsightControllerInterface', 'App\Analysing\\Controller\\Analytics\\InsightController');

    $services->alias('App\Analysing\\DomainInterface\\Analytics\\AnalyticsInterface', 'App\Analysing\\Domain\\Analytics\\Analytics');
    $services->alias('App\Analysing\\DomainInterface\\Analytics\\ExperimentInterface', 'App\Analysing\\Domain\\Analytics\\Experiment');
    $services->alias('App\Analysing\\DomainInterface\\Analytics\\FlagInterface', 'App\Analysing\\Domain\\Analytics\\Flag');
    $services->alias('App\Analysing\\DomainInterface\\Analytics\\InsightInterface', 'App\Analysing\\Domain\\Analytics\\Insight');
    $services->alias('App\Analysing\\DomainInterface\\Analytics\\ClickhouseClientInterface', 'App\Analysing\\Domain\\Analytics\\ClickhouseClient');

    $services->alias('App\Analysing\RepositoryInterface\Analytics\InfraRepositoryInterface', $sampleRuntime ? SampleInfraRepository::class : 'App\Analysing\Repository\Analytics\InfraRepository');

    $services->alias('App\Analysing\\ServiceInterface\\Alerts\\AlertEvaluatorInterface', 'App\Analysing\\Service\\Alerts\\AlertEvaluator');
    $services->alias('App\Analysing\\ServiceInterface\\Alerts\\NotificationDispatcherInterface', 'App\Analysing\\Service\\Alerts\\NotificationDispatcher');
    $services->alias('App\Analysing\\ServiceInterface\\Analytics\\AnalyticsCollectorInterface', 'App\Analysing\\Service\\Analytics\\AnalyticsCollector');
    $services->alias('App\Analysing\\ServiceInterface\\Analytics\\ExperimentServiceInterface', 'App\Analysing\\Service\\Analytics\\ExperimentService');
    $services->alias('App\Analysing\\ServiceInterface\\Analytics\\MetricIngestServiceInterface', 'App\Analysing\\Service\\Analytics\\MetricIngestService');
    $services->alias('App\Analysing\\ServiceInterface\\Analytics\\RollupServiceInterface', 'App\Analysing\\Service\\Analytics\\RollupService');
    $services->alias('App\Analysing\\ServiceInterface\\Analytics\\SegmentationServiceInterface', 'App\Analysing\\Service\\Analytics\\SegmentationService');
    $services->alias('App\Analysing\\ServiceInterface\\Analytics\\CsvImporterInterface', 'App\Analysing\\Service\\Analytics\\CsvImporter');
    $services->alias('App\Analysing\\ServiceInterface\\Analytics\\FileNotifierInterface', 'App\Analysing\\Service\\Analytics\\FileNotifier');
    $services->alias('App\Analysing\\ServiceInterface\\Analytics\\GzipWriterInterface', 'App\Analysing\\Service\\Analytics\\GzipWriter');
    $services->alias('App\Analysing\\ServiceInterface\\Analytics\\LocalCacheInterface', 'App\Analysing\\Service\\Analytics\\LocalCache');
    $services->alias('App\Analysing\\ServiceInterface\\Analytics\\WebhookNotifierInterface', 'App\Analysing\\Service\\Analytics\\WebhookNotifier');
    $services->alias('App\Analysing\\ServiceInterface\\Analytics\\AsyncQueryServiceInterface', 'App\Analysing\\Service\\Analytics\\AsyncQueryService');
    $services->alias('App\Analysing\\ServiceInterface\\Analytics\\BackfillServiceInterface', 'App\Analysing\\Service\\Analytics\\BackfillService');
    $services->alias('App\Analysing\\ServiceInterface\\Analytics\\ReportExporterServiceInterface', 'App\Analysing\\Service\\Analytics\\ReportExporterService');
    $services->alias('App\Analysing\\ServiceInterface\\Analytics\\ReportGeneratorServiceInterface', 'App\Analysing\\Service\\Analytics\\ReportGeneratorService');
    $services->alias('App\Analysing\\ServiceInterface\\Analytics\\RetentionServiceInterface', 'App\Analysing\\Service\\Analytics\\RetentionService');
    $services->alias('App\Analysing\\ServiceInterface\\Analytics\\TokenServiceInterface', 'App\Analysing\\Service\\Analytics\\TokenService');
    $services->alias('App\Analysing\\ServiceInterface\\Analytics\\AccessGuardInterface', 'App\Analysing\\Service\\Analytics\\AccessGuard');
    $services->alias('App\Analysing\\ServiceInterface\\Analytics\\AggregateServiceInterface', 'App\Analysing\\Service\\Analytics\\AggregateService');
    $services->alias('App\Analysing\\ServiceInterface\\Analytics\\AnomalyDetectorInterface', 'App\Analysing\\Service\\Analytics\\AnomalyDetector');
    $services->alias('App\Analysing\\ServiceInterface\\Analytics\\CacheInterface', 'App\Analysing\\Service\\Analytics\\LocalCache');
    $services->alias('App\Analysing\\ServiceInterface\\Analytics\\CsvImportInterface', 'App\Analysing\\Service\\Analytics\\CsvImporter');
    $services->alias('App\Analysing\ServiceInterface\Analytics\DashboardServiceInterface', $sampleRuntime ? SampleDashboardService::class : 'App\Analysing\Service\Analytics\DashboardService');
    $services->alias('App\Analysing\\ServiceInterface\\Analytics\\HealthServiceInterface', 'App\Analysing\\Service\\Analytics\\HealthService');
    $services->alias('App\Analysing\\ServiceInterface\\Analytics\\KpiRegistryInterface', 'App\Analysing\\Service\\Analytics\\KpiRegistry');
    $services->alias('App\Analysing\\ServiceInterface\\Analytics\\MetricIngestInterface', 'App\Analysing\\Service\\Analytics\\MetricIngestService');
    $services->alias('App\Analysing\\ServiceInterface\\Analytics\\NotifierInterface', 'App\Analysing\\Service\\Analytics\\WebhookNotifier');
    $services->alias('App\Analysing\\ServiceInterface\\Analytics\\ReportBundleInterface', 'App\Analysing\\Service\\Analytics\\ReportBundle');
    $services->alias('App\Analysing\\ServiceInterface\\Analytics\\RetentionInterface', 'App\Analysing\\Service\\Analytics\\RetentionService');
    $services->alias('App\Analysing\\ServiceInterface\\Analytics\\RollupInterface', 'App\Analysing\\Service\\Analytics\\RollupService');
    $services->alias('App\Analysing\\ServiceInterface\\Analytics\\SegmentationInterface', 'App\Analysing\\Service\\Analytics\\SegmentationService');
    $services->alias('App\Analysing\\ServiceInterface\\Analytics\\SloCalculatorInterface', 'App\Analysing\\Service\\Analytics\\SloCalculator');
    $services->alias('App\Analysing\\ServiceInterface\\Analytics\\TenantScopeInterface', 'App\Analysing\\Service\\Analytics\\TenantScope');
    $services->alias('App\Analysing\\ServiceInterface\\Analytics\\TransformerInterface', 'App\Analysing\\Service\\Analytics\\Transformer');
    $services->alias('App\Analysing\\ServiceInterface\\Analytics\\WindowQueryInterface', 'App\Analysing\\Service\\Analytics\\WindowQuery');

    $services->alias('App\Analysing\RepositoryInterface\Analytics\SampleInfraRepositoryInterface', 'App\Analysing\Repository\Analytics\SampleInfraRepository');
    $services->alias('App\Analysing\ServiceInterface\Analytics\DashboardHtmlRendererInterface', 'App\Analysing\Service\Analytics\DashboardHtmlRenderer');
    $services->alias('App\Analysing\ServiceInterface\Analytics\SampleAnalyticsDatasetInterface', 'App\Analysing\Service\Analytics\SampleAnalyticsDataset');
    $services->alias('App\Analysing\ServiceInterface\Analytics\SampleDashboardServiceInterface', 'App\Analysing\Service\Analytics\SampleDashboardService');
    $services->alias('App\Analysing\ServiceInterface\Http\AnalyticsErrorResponseFactoryInterface', 'App\Analysing\Service\Http\AnalyticsErrorResponseFactory');
    $services->alias('App\Analysing\ServiceInterface\Http\AnalyticsIdempotencyRequestSubscriberInterface', 'App\Analysing\Service\Http\AnalyticsIdempotencyRequestSubscriber');
    $services->alias('App\Analysing\ServiceInterface\Http\AnalyticsIdempotencyResponseSubscriberInterface', 'App\Analysing\Service\Http\AnalyticsIdempotencyResponseSubscriber');
    $services->alias('App\Analysing\ServiceInterface\Http\AnalyticsIdempotencyStoreInterface', 'App\Analysing\Service\Http\AnalyticsIdempotencyStore');
    $services->alias('App\Analysing\ServiceInterface\Http\AnalyticsRateLimitResponseSubscriberInterface', 'App\Analysing\Service\Http\AnalyticsRateLimitResponseSubscriber');
    $services->alias('App\Analysing\ServiceInterface\Http\AnalyticsRequestAuthSubscriberInterface', 'App\Analysing\Service\Http\AnalyticsRequestAuthSubscriber');
    $services->alias('App\Analysing\ServiceInterface\Http\AnalyticsRouteRateLimiterInterface', 'App\Analysing\Service\Http\AnalyticsRouteRateLimiter');
    $services->alias('App\Analysing\ServiceInterface\Http\AnalyticsSuccessResponseFactoryInterface', 'App\Analysing\Service\Http\AnalyticsSuccessResponseFactory');
    $services->alias('App\Analysing\ServiceInterface\Http\AnalyticsWriteRateLimitSubscriberInterface', 'App\Analysing\Service\Http\AnalyticsWriteRateLimitSubscriber');
    $services->alias('App\Analysing\ServiceInterface\Http\RequestCorrelationIdProviderInterface', 'App\Analysing\Service\Http\RequestCorrelationIdProvider');
    $services->alias('App\Analysing\ServiceInterface\Http\RequestCorrelationIdSubscriberInterface', 'App\Analysing\Service\Http\RequestCorrelationIdSubscriber');
    $services->alias('App\Analysing\ServiceInterface\Http\TenantContextInterface', 'App\Analysing\Service\Http\TenantContext');
    $services->alias('App\Analysing\ServiceInterface\Http\TenantContextResolverInterface', 'App\Analysing\Service\Http\TenantContextResolver');
    $services->alias('App\Analysing\ServiceInterface\Http\TenantContextResponseSubscriberInterface', 'App\Analysing\Service\Http\TenantContextResponseSubscriber');
    $services->alias('App\Analysing\ServiceInterface\Http\TenantContextSubscriberInterface', 'App\Analysing\Service\Http\TenantContextSubscriber');

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

    $services->set('App\Analysing\Service\Http\AnalyticsRequestAuthSubscriber')
        ->arg('$required', param('analytics.auth.required'))
        ->arg('$publicRead', param('analytics.auth.public_read'));

    $services->set('App\Analysing\Service\Http\AnalyticsIdempotencyRequestSubscriber')
        ->arg('$enabled', param('analytics.idempotency.enabled'))
        ->arg('$required', param('analytics.idempotency.required'));

    $services->set('App\Analysing\Service\Http\AnalyticsIdempotencyResponseSubscriber')
        ->arg('$enabled', param('analytics.idempotency.enabled'));

    $services->set('App\Analysing\Service\Http\AnalyticsWriteRateLimitSubscriber');

    $services->set('App\Analysing\Service\Http\AnalyticsRateLimitResponseSubscriber');

    $services->set(NullLogger::class);
    $services->alias(LoggerInterface::class, NullLogger::class);
};
