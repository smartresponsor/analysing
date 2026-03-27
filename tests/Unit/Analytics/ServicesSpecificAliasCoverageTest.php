<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use PHPUnit\Framework\TestCase;

final class ServicesSpecificAliasCoverageTest extends TestCase
{
    public function testSpecificAnalyticsServiceInterfacesAreAliasedInServicesYaml(): void
    {
        $yaml = (string) file_get_contents(__DIR__.'/../../../config/services.yaml');

        $expected = [
            "App\\ServiceInterface\\Alerts\\AlertEvaluatorInterface: '@App\\Service\\Alerts\\AlertEvaluator'",
            "App\\ServiceInterface\\Alerts\\NotificationDispatcherInterface: '@App\\Service\\Alerts\\NotificationDispatcher'",
            "App\\ServiceInterface\\Analytics\\AnalyticsCollectorInterface: '@App\\Service\\Analytics\\AnalyticsCollector'",
            "App\\ServiceInterface\\Analytics\\ExperimentServiceInterface: '@App\\Service\\Analytics\\ExperimentService'",
            "App\\ServiceInterface\\Analytics\\MetricIngestServiceInterface: '@App\\Service\\Analytics\\MetricIngestService'",
            "App\\ServiceInterface\\Analytics\\RollupServiceInterface: '@App\\Service\\Analytics\\RollupService'",
            "App\\ServiceInterface\\Analytics\\SegmentationServiceInterface: '@App\\Service\\Analytics\\SegmentationService'",
            "App\\ServiceInterface\\Analytics\\CsvImporterInterface: '@App\\Service\\Analytics\\CsvImporter'",
            "App\\ServiceInterface\\Analytics\\FileNotifierInterface: '@App\\Service\\Analytics\\FileNotifier'",
            "App\\ServiceInterface\\Analytics\\GzipWriterInterface: '@App\\Service\\Analytics\\GzipWriter'",
            "App\\ServiceInterface\\Analytics\\LocalCacheInterface: '@App\\Service\\Analytics\\LocalCache'",
            "App\\ServiceInterface\\Analytics\\WebhookNotifierInterface: '@App\\Service\\Analytics\\WebhookNotifier'",
            "App\\ServiceInterface\\Analytics\\AsyncQueryServiceInterface: '@App\\Service\\Analytics\\AsyncQueryService'",
            "App\\ServiceInterface\\Analytics\\BackfillServiceInterface: '@App\\Service\\Analytics\\BackfillService'",
            "App\\ServiceInterface\\Analytics\\ReportExporterServiceInterface: '@App\\Service\\Analytics\\ReportExporterService'",
            "App\\ServiceInterface\\Analytics\\ReportGeneratorServiceInterface: '@App\\Service\\Analytics\\ReportGeneratorService'",
            "App\\ServiceInterface\\Analytics\\RetentionServiceInterface: '@App\\Service\\Analytics\\RetentionService'",
            "App\\ServiceInterface\\Analytics\\TokenServiceInterface: '@App\\Service\\Analytics\\TokenService'",
        ];

        foreach ($expected as $alias) {
            self::assertStringContainsString($alias, $yaml);
        }
    }
}
