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
            "App\Analysing\\ServiceInterface\\Alerts\\AlertEvaluatorInterface: '@App\Analysing\\Service\\Alerts\\AlertEvaluator'",
            "App\Analysing\\ServiceInterface\\Alerts\\NotificationDispatcherInterface: '@App\Analysing\\Service\\Alerts\\NotificationDispatcher'",
            "App\Analysing\\ServiceInterface\\Analytics\\AnalyticsCollectorInterface: '@App\Analysing\\Service\\Analytics\\AnalyticsCollector'",
            "App\Analysing\\ServiceInterface\\Analytics\\ExperimentServiceInterface: '@App\Analysing\\Service\\Analytics\\ExperimentService'",
            "App\Analysing\\ServiceInterface\\Analytics\\MetricIngestServiceInterface: '@App\Analysing\\Service\\Analytics\\MetricIngestService'",
            "App\Analysing\\ServiceInterface\\Analytics\\RollupServiceInterface: '@App\Analysing\\Service\\Analytics\\RollupService'",
            "App\Analysing\\ServiceInterface\\Analytics\\SegmentationServiceInterface: '@App\Analysing\\Service\\Analytics\\SegmentationService'",
            "App\Analysing\\ServiceInterface\\Analytics\\CsvImporterInterface: '@App\Analysing\\Service\\Analytics\\CsvImporter'",
            "App\Analysing\\ServiceInterface\\Analytics\\FileNotifierInterface: '@App\Analysing\\Service\\Analytics\\FileNotifier'",
            "App\Analysing\\ServiceInterface\\Analytics\\GzipWriterInterface: '@App\Analysing\\Service\\Analytics\\GzipWriter'",
            "App\Analysing\\ServiceInterface\\Analytics\\LocalCacheInterface: '@App\Analysing\\Service\\Analytics\\LocalCache'",
            "App\Analysing\\ServiceInterface\\Analytics\\WebhookNotifierInterface: '@App\Analysing\\Service\\Analytics\\WebhookNotifier'",
            "App\Analysing\\ServiceInterface\\Analytics\\AsyncQueryServiceInterface: '@App\Analysing\\Service\\Analytics\\AsyncQueryService'",
            "App\Analysing\\ServiceInterface\\Analytics\\BackfillServiceInterface: '@App\Analysing\\Service\\Analytics\\BackfillService'",
            "App\Analysing\\ServiceInterface\\Analytics\\ReportExporterServiceInterface: '@App\Analysing\\Service\\Analytics\\ReportExporterService'",
            "App\Analysing\\ServiceInterface\\Analytics\\ReportGeneratorServiceInterface: '@App\Analysing\\Service\\Analytics\\ReportGeneratorService'",
            "App\Analysing\\ServiceInterface\\Analytics\\RetentionServiceInterface: '@App\Analysing\\Service\\Analytics\\RetentionService'",
            "App\Analysing\\ServiceInterface\\Analytics\\TokenServiceInterface: '@App\Analysing\\Service\\Analytics\\TokenService'",
        ];

        foreach ($expected as $alias) {
            self::assertStringContainsString($alias, $yaml);
        }
    }
}
