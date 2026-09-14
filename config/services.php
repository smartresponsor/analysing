<?php

declare(strict_types=1);

use App\Analysing\Factory\Doctrine\AnalyticsEntityManagerFactory;
use App\Analysing\Repository\AnalyticsSampleAnalyticsRepository;
use App\Analysing\Service\AnalyticsClickhouseClient;
use App\Analysing\Service\AnalyticsHealthService;
use App\Analysing\Service\AnalyticsSampleDashboardService;
use App\Analysing\Service\Config\AnalyticsAnalysingEnvironmentConfigService;
use App\Analysing\Service\Http\AnalyticsIdempotencyStore;
use App\Analysing\Service\Http\AnalyticsRouteRateLimiter;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Schema\DefaultSchemaManagerFactory;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Yaml\Yaml;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $parameters = $container->parameters();
    $runtimePath = dirname(__DIR__).'/component/analytics_runtime.yaml';
    $runtime = is_file($runtimePath) ? Yaml::parseFile($runtimePath) : [];
    $runtime = is_array($runtime) ? $runtime : [];
    $runtimeAnalytics = is_array($runtime['analytics'] ?? null) ? $runtime['analytics'] : [];

    $parameters->set('analytics.clickhouse.base', (string) ($runtimeAnalytics['clickhouse_base'] ?? 'http://127.0.0.1:8123'));
    $parameters->set('analytics.clickhouse.user', (string) ($runtimeAnalytics['clickhouse_user'] ?? 'default'));
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

    $idempotencyEnabled = filter_var((string) ($runtimeAnalytics['idempotency_enabled'] ?? (getenv('ANALYTICS_IDEMPOTENCY_ENABLED') ?: '1')), FILTER_VALIDATE_BOOL);
    $idempotencyRequired = filter_var((string) ($runtimeAnalytics['idempotency_required'] ?? (getenv('ANALYTICS_IDEMPOTENCY_REQUIRED') ?: '0')), FILTER_VALIDATE_BOOL);
    $idempotencyTtl = (int) (getenv('ANALYTICS_IDEMPOTENCY_TTL_SECONDS') ?: 86400);
    $idempotencyDirectory = trim((string) (getenv('ANALYTICS_IDEMPOTENCY_DIRECTORY') ?: dirname(__DIR__).'/var/analytics_idempotency'));

    $parameters->set('analytics.idempotency.enabled', $idempotencyEnabled);
    $parameters->set('analytics.idempotency.required', $idempotencyRequired);
    $parameters->set('analytics.idempotency.mode', $idempotencyEnabled ? 'local_file' : 'disabled');
    $parameters->set('analytics.idempotency.ttl_seconds', max(60, $idempotencyTtl));
    $parameters->set('analytics.idempotency.directory', '' !== $idempotencyDirectory ? $idempotencyDirectory : dirname(__DIR__).'/var/analytics_idempotency');

    $authRequired = filter_var((string) ($runtimeAnalytics['auth_required'] ?? (getenv('ANALYTICS_AUTH_REQUIRED') ?: '0')), FILTER_VALIDATE_BOOL);
    $authPublicRead = filter_var((string) ($runtimeAnalytics['auth_public_read'] ?? (getenv('ANALYTICS_AUTH_PUBLIC_READ') ?: '1')), FILTER_VALIDATE_BOOL);

    $parameters->set('analytics.auth.required', $authRequired);
    $parameters->set('analytics.auth.public_read', $authPublicRead);

    $rateLimitEnabled = filter_var((string) ($runtimeAnalytics['rate_limit_enabled'] ?? (getenv('ANALYTICS_RATE_LIMIT_ENABLED') ?: '1')), FILTER_VALIDATE_BOOL);
    $rateLimitWindowSeconds = (int) ($runtimeAnalytics['rate_limit_window_seconds'] ?? (int) (getenv('ANALYTICS_RATE_LIMIT_WINDOW_SECONDS') ?: 60));
    $rateLimitDefaultWriteLimit = (int) ($runtimeAnalytics['rate_limit_default_write_limit'] ?? (int) (getenv('ANALYTICS_RATE_LIMIT_DEFAULT_WRITE_LIMIT') ?: 60));
    $rateLimitDirectory = trim((string) (getenv('ANALYTICS_RATE_LIMIT_DIRECTORY') ?: dirname(__DIR__).'/var/analytics_rate_limit'));

    $parameters->set('analytics.rate_limit.enabled', $rateLimitEnabled);
    $parameters->set('analytics.rate_limit.mode', $rateLimitEnabled ? 'local_file' : 'disabled');
    $parameters->set('analytics.rate_limit.window_seconds', max(1, $rateLimitWindowSeconds));
    $parameters->set('analytics.rate_limit.default_write_limit', max(1, $rateLimitDefaultWriteLimit));
    $parameters->set('analytics.rate_limit.directory', '' !== $rateLimitDirectory ? $rateLimitDirectory : dirname(__DIR__).'/var/analytics_rate_limit');

    $services = $container->services();
    $services->defaults()
        ->autowire()
        ->autoconfigure();

    $services->set('app.analysing.entity_manager')
        ->class(Doctrine\ORM\EntityManager::class)
        ->factory([AnalyticsEntityManagerFactory::class, 'create']);

    $services->defaults()
        ->autowire()
        ->autoconfigure()
        ->bind(EntityManagerInterface::class, service('app.analysing.entity_manager'));

    $services->load('App\Analysing\\Controller\\', '../src/Controller/')
        ->tag('controller.service_arguments')
        ->public();

    $services->load('App\Analysing\\Command\\', '../src/Command/');
    $services->load('App\Analysing\\Form\\', '../src/Form/');
    $services->load('App\Analysing\\Repository\\', '../src/Repository/');
    $services->load('App\Analysing\\Service\\', '../src/Service/');
    $services->load('App\Analysing\\Builder\\', '../src/Builder/');
    $services->load('App\Analysing\\EventSubscriber\\', '../src/EventSubscriber/');
    $services->load('App\Analysing\\Factory\\', '../src/Factory/');
    $services->load('App\Analysing\\Policy\\', '../src/Policy/');
    $services->load('App\Analysing\\Provider\\', '../src/Provider/');
    $services->load('App\Analysing\\Resolver\\', '../src/Resolver/');

    $services->alias('App\Analysing\Controller\AnalyticsAggregateControllerInterface', 'App\Analysing\Controller\AnalyticsAggregateController');
    $services->alias('App\Analysing\Controller\AnalyticsControllerInterface', 'App\Analysing\Controller\AnalyticsController');
    $services->alias('App\Analysing\Controller\AnalyticsApiControllerInterface', 'App\Analysing\Controller\AnalyticsApiController');
    $services->alias('App\Analysing\Controller\AnalyticsDashboardControllerInterface', 'App\Analysing\Controller\AnalyticsDashboardController');
    $services->alias('App\Analysing\Controller\AnalyticsDashboardPageControllerInterface', 'App\Analysing\Controller\AnalyticsDashboardPageController');
    $services->alias('App\Analysing\Controller\AnalyticsExperimentControllerInterface', 'App\Analysing\Controller\AnalyticsExperimentController');
    $services->alias('App\Analysing\Controller\AnalyticsFlagControllerInterface', 'App\Analysing\Controller\AnalyticsFlagController');
    $services->alias('App\Analysing\Controller\AnalyticsHealthControllerInterface', 'App\Analysing\Controller\AnalyticsHealthController');
    $services->alias('App\Analysing\Controller\AnalyticsIngestControllerInterface', 'App\Analysing\Controller\AnalyticsIngestController');
    $services->alias('App\Analysing\Controller\AnalyticsInsightControllerInterface', 'App\Analysing\Controller\AnalyticsInsightController');
    $services->alias('App\Analysing\Controller\AnalyticsExportJobControllerInterface', 'App\Analysing\Controller\AnalyticsExportJobController');

    $services->alias('App\Analysing\ServiceInterface\AnalyticsInterface', 'App\Analysing\Service\Analytics');
    $services->alias('App\Analysing\ServiceInterface\AnalyticsExperimentInterface', 'App\Analysing\Service\AnalyticsExperiment');
    $services->alias('App\Analysing\ServiceInterface\AnalyticsFlagInterface', 'App\Analysing\Service\AnalyticsFlag');
    $services->alias('App\Analysing\ServiceInterface\AnalyticsInsightInterface', 'App\Analysing\Service\AnalyticsInsight');
    $services->alias('App\Analysing\ServiceInterface\AnalyticsClickhouseClientInterface', 'App\Analysing\Service\AnalyticsClickhouseClient');

    $services->set(AnalyticsClickhouseClient::class)
        ->arg('$base', param('analytics.clickhouse.base'))
        ->arg('$user', param('analytics.clickhouse.user'))
        ->arg('$pass', param('analytics.clickhouse.pass'));

    $services->set('App\Analysing\Service\AnalyticsExperiment')
        ->arg('$client', service(AnalyticsClickhouseClient::class))
        ->arg('$logger', service(LoggerInterface::class))
        ->arg('$salt', param('analytics.runtime.salt'));

    $services->set('App\Analysing\Service\AnalyticsFlag')
        ->arg('$client', service(AnalyticsClickhouseClient::class))
        ->arg('$logger', service(LoggerInterface::class))
        ->arg('$salt', param('analytics.runtime.salt'));

    $services->alias('App\Analysing\RepositoryInterface\AnalyticsRepositoryInterface', $sampleRuntime ? AnalyticsSampleAnalyticsRepository::class : 'App\Analysing\Repository\AnalyticsRepository');

    $services->alias('App\Analysing\\ServiceInterface\\Alerts\\AnalyticsAlertEvaluatorInterface', 'App\Analysing\\Service\\Alerts\\AnalyticsAlertEvaluator');
    $services->alias('App\Analysing\\ServiceInterface\\Alerts\\AnalyticsNotificationDispatcherInterface', 'App\Analysing\\Service\\Alerts\\AnalyticsNotificationDispatcher');
    $services->alias('App\Analysing\ServiceInterface\AnalyticsCollectorInterface', 'App\Analysing\Service\AnalyticsCollector');
    $services->alias('App\Analysing\ServiceInterface\AnalyticsExperimentServiceInterface', 'App\Analysing\Service\AnalyticsExperimentService');
    $services->alias('App\Analysing\ServiceInterface\AnalyticsMetricIngestServiceInterface', 'App\Analysing\Service\AnalyticsMetricIngestService');
    $services->alias('App\Analysing\ServiceInterface\AnalyticsRollupServiceInterface', 'App\Analysing\Service\AnalyticsRollupService');
    $services->alias('App\Analysing\ServiceInterface\AnalyticsSegmentationServiceInterface', 'App\Analysing\Service\AnalyticsSegmentationService');
    $services->alias('App\Analysing\ServiceInterface\AnalyticsCsvImporterInterface', 'App\Analysing\Service\AnalyticsCsvImporter');
    $services->alias('App\Analysing\ServiceInterface\AnalyticsFileNotifierInterface', 'App\Analysing\Service\AnalyticsFileNotifier');
    $services->alias('App\Analysing\ServiceInterface\AnalyticsGzipWriterInterface', 'App\Analysing\Service\AnalyticsGzipWriter');
    $services->alias('App\Analysing\ServiceInterface\AnalyticsLocalCacheInterface', 'App\Analysing\Service\AnalyticsLocalCache');
    $services->alias('App\Analysing\ServiceInterface\AnalyticsWebhookNotifierInterface', 'App\Analysing\Service\AnalyticsWebhookNotifier');
    $services->alias('App\Analysing\ServiceInterface\AnalyticsAsyncQueryServiceInterface', 'App\Analysing\Service\AnalyticsAsyncQueryService');
    $services->alias('App\Analysing\ServiceInterface\AnalyticsBackfillServiceInterface', 'App\Analysing\Service\AnalyticsBackfillService');
    $services->alias('App\Analysing\ServiceInterface\AnalyticsReportExporterServiceInterface', 'App\Analysing\Service\AnalyticsReportExporterService');
    $services->alias('App\Analysing\ServiceInterface\AnalyticsReportGeneratorServiceInterface', 'App\Analysing\Service\AnalyticsReportGeneratorService');
    $services->alias('App\Analysing\ServiceInterface\AnalyticsRetentionServiceInterface', 'App\Analysing\Service\AnalyticsRetentionService');
    $services->alias('App\Analysing\ServiceInterface\AnalyticsTokenServiceInterface', 'App\Analysing\Service\AnalyticsTokenService');
    $services->alias('App\Analysing\ServiceInterface\AnalyticsAccessGuardInterface', 'App\Analysing\Service\AnalyticsAccessGuard');
    $services->alias('App\Analysing\ServiceInterface\AnalyticsAggregateServiceInterface', 'App\Analysing\Service\AnalyticsAggregateService');
    $services->alias('App\Analysing\ServiceInterface\AnalyticsAnomalyDetectorInterface', 'App\Analysing\Service\AnalyticsAnomalyDetector');
    $services->alias('App\Analysing\ServiceInterface\AnalyticsCacheInterface', 'App\Analysing\Service\AnalyticsLocalCache');
    $services->alias('App\Analysing\ServiceInterface\AnalyticsCsvImportInterface', 'App\Analysing\Service\AnalyticsCsvImporter');
    $services->alias('App\Analysing\ServiceInterface\AnalyticsDashboardServiceInterface', $sampleRuntime ? AnalyticsSampleDashboardService::class : 'App\Analysing\Service\AnalyticsDashboardService');
    $services->alias('App\Analysing\ServiceInterface\AnalyticsHealthServiceInterface', 'App\Analysing\Service\AnalyticsHealthService');
    $services->alias('App\Analysing\ServiceInterface\AnalyticsKpiRegistryInterface', 'App\Analysing\Service\AnalyticsKpiRegistry');
    $services->alias('App\Analysing\ServiceInterface\AnalyticsMetricIngestInterface', 'App\Analysing\Service\AnalyticsMetricIngestService');
    $services->alias('App\Analysing\ServiceInterface\AnalyticsNotifierInterface', 'App\Analysing\Service\AnalyticsWebhookNotifier');
    $services->alias('App\Analysing\ServiceInterface\AnalyticsReportBundleInterface', 'App\Analysing\Service\AnalyticsReportBundle');
    $services->alias('App\Analysing\ServiceInterface\AnalyticsRetentionInterface', 'App\Analysing\Service\AnalyticsRetentionService');
    $services->alias('App\Analysing\ServiceInterface\AnalyticsRollupInterface', 'App\Analysing\Service\AnalyticsRollupService');
    $services->alias('App\Analysing\ServiceInterface\AnalyticsSegmentationInterface', 'App\Analysing\Service\AnalyticsSegmentationService');
    $services->alias('App\Analysing\ServiceInterface\AnalyticsSloCalculatorInterface', 'App\Analysing\Service\AnalyticsSloCalculator');
    $services->alias('App\Analysing\ServiceInterface\AnalyticsVendorScopeInterface', 'App\Analysing\Service\AnalyticsVendorScope');
    $services->alias('App\Analysing\ServiceInterface\AnalyticsTransformerInterface', 'App\Analysing\Service\AnalyticsTransformer');
    $services->alias('App\Analysing\ServiceInterface\AnalyticsWindowQueryInterface', 'App\Analysing\Service\AnalyticsWindowQuery');
    $services->alias('App\Analysing\Policy\AnalyticsPipelineLifecyclePolicyInterface', 'App\Analysing\Policy\AnalyticsPipelineLifecyclePolicy');
    $services->alias('App\Analysing\FactoryInterface\AnalyticsDashboardRequestFactoryInterface', 'App\Analysing\Factory\AnalyticsDashboardRequestFactory');
    $services->alias('App\Analysing\ServiceInterface\AnalyticsExportJobLockManagerInterface', 'App\Analysing\Service\AnalyticsExportJobLockManager');
    $services->alias('App\Analysing\ServiceInterface\AnalyticsExportJobMetricsServiceInterface', 'App\Analysing\Service\AnalyticsExportJobMetricsService');
    $services->alias('App\Analysing\ServiceInterface\AnalyticsExportJobRunnerInterface', 'App\Analysing\Service\AnalyticsExportJobRunner');
    $services->alias('App\Analysing\ServiceInterface\AnalyticsExportJobViewInterface', 'App\Analysing\Service\AnalyticsExportJobView');
    $services->alias('App\Analysing\BuilderInterface\AnalyticsReportRowBuilderInterface', 'App\Analysing\Builder\AnalyticsReportRowBuilder');

    $services->alias('App\Analysing\RepositoryInterface\AnalyticsSampleAnalyticsRepositoryInterface', 'App\Analysing\Repository\AnalyticsSampleAnalyticsRepository');
    $services->alias('App\Analysing\ServiceInterface\AnalyticsDashboardHtmlRendererInterface', 'App\Analysing\Service\AnalyticsDashboardHtmlRenderer');
    $services->alias('App\Analysing\ServiceInterface\AnalyticsSampleAnalyticsDatasetInterface', 'App\Analysing\Service\AnalyticsSampleAnalyticsDataset');
    $services->alias('App\Analysing\ServiceInterface\AnalyticsSampleDashboardServiceInterface', 'App\Analysing\Service\AnalyticsSampleDashboardService');
    $services->alias('App\Analysing\FactoryInterface\Http\AnalyticsErrorResponseFactoryInterface', 'App\Analysing\Factory\Http\AnalyticsErrorResponseFactory');
    $services->alias('App\Analysing\EventSubscriber\Http\AnalyticsIdempotencyRequestSubscriberInterface', 'App\Analysing\EventSubscriber\Http\AnalyticsIdempotencyRequestSubscriber');
    $services->alias('App\Analysing\EventSubscriber\Http\AnalyticsIdempotencyResponseSubscriberInterface', 'App\Analysing\EventSubscriber\Http\AnalyticsIdempotencyResponseSubscriber');
    $services->alias('App\Analysing\ServiceInterface\Http\AnalyticsIdempotencyStoreInterface', 'App\Analysing\Service\Http\AnalyticsIdempotencyStore');
    $services->alias('App\Analysing\EventSubscriber\Http\AnalyticsRateLimitResponseSubscriberInterface', 'App\Analysing\EventSubscriber\Http\AnalyticsRateLimitResponseSubscriber');
    $services->alias('App\Analysing\EventSubscriber\Http\AnalyticsRequestAuthSubscriberInterface', 'App\Analysing\EventSubscriber\Http\AnalyticsRequestAuthSubscriber');
    $services->alias('App\Analysing\ServiceInterface\Http\AnalyticsRouteRateLimiterInterface', 'App\Analysing\Service\Http\AnalyticsRouteRateLimiter');
    $services->alias('App\Analysing\FactoryInterface\Http\AnalyticsSuccessResponseFactoryInterface', 'App\Analysing\Factory\Http\AnalyticsSuccessResponseFactory');
    $services->alias('App\Analysing\EventSubscriber\Http\AnalyticsWriteRateLimitSubscriberInterface', 'App\Analysing\EventSubscriber\Http\AnalyticsWriteRateLimitSubscriber');
    $services->alias('App\Analysing\ProviderInterface\Http\AnalyticsRequestCorrelationIdProviderInterface', 'App\Analysing\Provider\Http\AnalyticsRequestCorrelationIdProvider');
    $services->alias('App\Analysing\EventSubscriber\Http\AnalyticsRequestCorrelationIdSubscriberInterface', 'App\Analysing\EventSubscriber\Http\AnalyticsRequestCorrelationIdSubscriber');
    $services->alias('App\Analysing\ServiceInterface\Http\AnalyticsVendorContextInterface', 'App\Analysing\Service\Http\AnalyticsVendorContext');
    $services->alias('App\Analysing\Resolver\Http\AnalyticsVendorContextResolverInterface', 'App\Analysing\Resolver\Http\AnalyticsVendorContextResolver');
    $services->alias('App\Analysing\EventSubscriber\Http\AnalyticsVendorContextResponseSubscriberInterface', 'App\Analysing\EventSubscriber\Http\AnalyticsVendorContextResponseSubscriber');
    $services->alias('App\Analysing\EventSubscriber\Http\AnalyticsVendorContextSubscriberInterface', 'App\Analysing\EventSubscriber\Http\AnalyticsVendorContextSubscriber');
    $services->alias('App\Analysing\ServiceInterface\Config\AnalyticsAnalysingEnvironmentConfigServiceInterface', 'App\Analysing\Service\Config\AnalyticsAnalysingEnvironmentConfigService');
    $services->alias('App\Analysing\FactoryInterface\Doctrine\AnalyticsEntityManagerFactoryInterface', 'App\Analysing\Factory\Doctrine\AnalyticsEntityManagerFactory');
    $services->alias('App\Analysing\ServiceInterface\Doctrine\AnalyticsStorageManagerInterface', 'App\Analysing\Service\Doctrine\AnalyticsStorageManager');
    $services->alias('App\Analysing\ServiceInterface\Http\AnalyticsJsonRequestBodyDecoderInterface', 'App\Analysing\Service\Http\AnalyticsJsonRequestBodyDecoder');

    $services->set(AnalyticsIdempotencyStore::class)
        ->arg('$logger', service(LoggerInterface::class))
        ->arg('$directory', param('analytics.idempotency.directory'))
        ->arg('$ttlSeconds', param('analytics.idempotency.ttl_seconds'));

    $services->set(AnalyticsRouteRateLimiter::class)
        ->arg('$logger', service(LoggerInterface::class))
        ->arg('$directory', param('analytics.rate_limit.directory'))
        ->arg('$windowSeconds', param('analytics.rate_limit.window_seconds'))
        ->arg('$defaultWriteLimit', param('analytics.rate_limit.default_write_limit'))
        ->arg('$enabled', param('analytics.rate_limit.enabled'));

    $services->set(AnalyticsHealthService::class)
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

    $services->set('App\Analysing\EventSubscriber\Http\AnalyticsRequestAuthSubscriber')
        ->arg('$required', param('analytics.auth.required'))
        ->arg('$publicRead', param('analytics.auth.public_read'));

    $services->set('App\Analysing\EventSubscriber\Http\AnalyticsIdempotencyRequestSubscriber')
        ->arg('$enabled', param('analytics.idempotency.enabled'))
        ->arg('$required', param('analytics.idempotency.required'));

    $services->set('App\Analysing\EventSubscriber\Http\AnalyticsIdempotencyResponseSubscriber')
        ->arg('$enabled', param('analytics.idempotency.enabled'));

    $services->set('App\Analysing\EventSubscriber\Http\AnalyticsWriteRateLimitSubscriber');

    $services->set('App\Analysing\EventSubscriber\Http\AnalyticsRateLimitResponseSubscriber');

    $services->set(AnalyticsAnalysingEnvironmentConfigService::class)
        ->arg('$projectDir', param('kernel.project_dir'))
        ->tag('administering.config_tool');

    $services->set(NullLogger::class);
    $services->alias(LoggerInterface::class, NullLogger::class);
};

if (!function_exists('analyticsDbalConfiguration')) {
    function analyticsDbalConfiguration(): Configuration
    {
        $configuration = new Configuration();
        $configuration->setSchemaManagerFactory(new DefaultSchemaManagerFactory());

        return $configuration;
    }
}

if (!function_exists('analyticsIsDebug')) {
    function analyticsIsDebug(): bool
    {
        $value = getenv('APP_DEBUG');

        return false !== $value && '0' !== $value && '' !== $value;
    }
}
