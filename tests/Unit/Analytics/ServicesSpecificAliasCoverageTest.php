<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use PHPUnit\Framework\TestCase;

final class ServicesSpecificAliasCoverageTest extends TestCase
{
    public function testSpecificAnalyticsServiceInterfacesAreAliasedInServicesYaml(): void
    {
        $yaml = (string) file_get_contents(__DIR__.'/../../../config/component/services.yaml');

        $expected = [
            "App\Analysing\\ServiceInterface\\Alerts\\AnalyticsAlertEvaluatorInterface: '@App\Analysing\\Service\\Alerts\\AnalyticsAlertEvaluator'",
            "App\Analysing\\ServiceInterface\\Alerts\\AnalyticsNotificationDispatcherInterface: '@App\Analysing\\Service\\Alerts\\AnalyticsNotificationDispatcher'",
            "App\Analysing\\ServiceInterface\\AnalyticsCollectorInterface: '@App\Analysing\\Service\\AnalyticsCollector'",
            "App\Analysing\ServiceInterface\AnalyticsExperimentServiceInterface: '@App\Analysing\Service\AnalyticsExperimentService'",
            "App\Analysing\ServiceInterface\AnalyticsMetricIngestServiceInterface: '@App\Analysing\Service\AnalyticsMetricIngestService'",
            "App\Analysing\ServiceInterface\AnalyticsRollupServiceInterface: '@App\Analysing\Service\AnalyticsRollupService'",
            "App\Analysing\ServiceInterface\AnalyticsSegmentationServiceInterface: '@App\Analysing\Service\AnalyticsSegmentationService'",
            "App\Analysing\\ServiceInterface\\AnalyticsCsvImporterInterface: '@App\Analysing\\Service\\AnalyticsCsvImporter'",
            "App\Analysing\\ServiceInterface\\AnalyticsFileNotifierInterface: '@App\Analysing\\Service\\AnalyticsFileNotifier'",
            "App\Analysing\\ServiceInterface\\AnalyticsGzipWriterInterface: '@App\Analysing\\Service\\AnalyticsGzipWriter'",
            "App\Analysing\\ServiceInterface\\AnalyticsLocalCacheInterface: '@App\Analysing\\Service\\AnalyticsLocalCache'",
            "App\Analysing\\ServiceInterface\\AnalyticsWebhookNotifierInterface: '@App\Analysing\\Service\\AnalyticsWebhookNotifier'",
            "App\Analysing\ServiceInterface\AnalyticsAsyncQueryServiceInterface: '@App\Analysing\Service\AnalyticsAsyncQueryService'",
            "App\Analysing\ServiceInterface\AnalyticsBackfillServiceInterface: '@App\Analysing\Service\AnalyticsBackfillService'",
            "App\Analysing\ServiceInterface\AnalyticsReportExporterServiceInterface: '@App\Analysing\Service\AnalyticsReportExporterService'",
            "App\Analysing\ServiceInterface\AnalyticsReportGeneratorServiceInterface: '@App\Analysing\Service\AnalyticsReportGeneratorService'",
            "App\Analysing\ServiceInterface\AnalyticsRetentionServiceInterface: '@App\Analysing\Service\AnalyticsRetentionService'",
            "App\Analysing\ServiceInterface\AnalyticsTokenServiceInterface: '@App\Analysing\Service\AnalyticsTokenService'",
        ];

        foreach ($expected as $alias) {
            self::assertStringContainsString($alias, $yaml);
        }
    }
}
