<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use PHPUnit\Framework\TestCase;

final class ServicesGenericAliasCoverageTest extends TestCase
{
    public function testGenericAnalyticsServiceInterfacesAreAliasedInServicesYaml(): void
    {
        $yaml = (string) file_get_contents(__DIR__.'/../../../config/component/services.yaml');

        $expected = [
            "App\Analysing\\ServiceInterface\\Analytics\\AccessGuardInterface: '@App\Analysing\\Service\\Analytics\\AccessGuard'",
            "App\Analysing\\ServiceInterface\\Analytics\\AggregateServiceInterface: '@App\Analysing\\Service\\Analytics\\AggregateService'",
            "App\Analysing\\ServiceInterface\\Analytics\\AnomalyDetectorInterface: '@App\Analysing\\Service\\Analytics\\AnomalyDetector'",
            "App\Analysing\\ServiceInterface\\Analytics\\CacheInterface: '@App\Analysing\\Service\\Analytics\\LocalCache'",
            "App\Analysing\\ServiceInterface\\Analytics\\CsvImportInterface: '@App\Analysing\\Service\\Analytics\\CsvImporter'",
            "App\Analysing\\ServiceInterface\\Analytics\\DashboardServiceInterface: '@App\Analysing\\Service\\Analytics\\DashboardService'",
            "App\Analysing\\ServiceInterface\\Analytics\\HealthServiceInterface: '@App\Analysing\\Service\\Analytics\\HealthService'",
            "App\Analysing\\ServiceInterface\\Analytics\\KpiRegistryInterface: '@App\Analysing\\Service\\Analytics\\KpiRegistry'",
            "App\Analysing\\ServiceInterface\\Analytics\\MetricIngestInterface: '@App\Analysing\\Service\\Analytics\\MetricIngestService'",
            "App\Analysing\\ServiceInterface\\Analytics\\NotifierInterface: '@App\Analysing\\Service\\Analytics\\WebhookNotifier'",
            "App\Analysing\\ServiceInterface\\Analytics\\ReportBundleInterface: '@App\Analysing\\Service\\Analytics\\ReportBundle'",
            "App\Analysing\\ServiceInterface\\Analytics\\RetentionInterface: '@App\Analysing\\Service\\Analytics\\RetentionService'",
            "App\Analysing\\ServiceInterface\\Analytics\\RollupInterface: '@App\Analysing\\Service\\Analytics\\RollupService'",
            "App\Analysing\\ServiceInterface\\Analytics\\SegmentationInterface: '@App\Analysing\\Service\\Analytics\\SegmentationService'",
            "App\Analysing\\ServiceInterface\\Analytics\\SloCalculatorInterface: '@App\Analysing\\Service\\Analytics\\SloCalculator'",
            "App\Analysing\\ServiceInterface\\Analytics\\TenantScopeInterface: '@App\Analysing\\Service\\Analytics\\TenantScope'",
            "App\Analysing\\ServiceInterface\\Analytics\\TransformerInterface: '@App\Analysing\\Service\\Analytics\\Transformer'",
            "App\Analysing\\ServiceInterface\\Analytics\\WindowQueryInterface: '@App\Analysing\\Service\\Analytics\\WindowQuery'",
            "Psr\\Log\\LoggerInterface: '@Psr\\Log\\NullLogger'",
        ];

        foreach ($expected as $alias) {
            self::assertStringContainsString($alias, $yaml);
        }
    }
}
