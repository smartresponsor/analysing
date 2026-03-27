<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use PHPUnit\Framework\TestCase;

final class ServicesGenericAliasCoverageTest extends TestCase
{
    public function testGenericAnalyticsServiceInterfacesAreAliasedInServicesYaml(): void
    {
        $yaml = (string) file_get_contents(__DIR__.'/../../../config/services.yaml');

        $expected = [
            "App\\ServiceInterface\\Analytics\\AccessGuardInterface: '@App\\Service\\Analytics\\AccessGuard'",
            "App\\ServiceInterface\\Analytics\\AggregateServiceInterface: '@App\\Service\\Analytics\\AggregateService'",
            "App\\ServiceInterface\\Analytics\\AnomalyDetectorInterface: '@App\\Service\\Analytics\\AnomalyDetector'",
            "App\\ServiceInterface\\Analytics\\CacheInterface: '@App\\Service\\Analytics\\LocalCache'",
            "App\\ServiceInterface\\Analytics\\CsvImportInterface: '@App\\Service\\Analytics\\CsvImporter'",
            "App\\ServiceInterface\\Analytics\\DashboardServiceInterface: '@App\\Service\\Analytics\\DashboardService'",
            "App\\ServiceInterface\\Analytics\\HealthServiceInterface: '@App\\Service\\Analytics\\HealthService'",
            "App\\ServiceInterface\\Analytics\\KpiRegistryInterface: '@App\\Service\\Analytics\\KpiRegistry'",
            "App\\ServiceInterface\\Analytics\\MetricIngestInterface: '@App\\Service\\Analytics\\MetricIngestService'",
            "App\\ServiceInterface\\Analytics\\NotifierInterface: '@App\\Service\\Analytics\\WebhookNotifier'",
            "App\\ServiceInterface\\Analytics\\ReportBundleInterface: '@App\\Service\\Analytics\\ReportBundle'",
            "App\\ServiceInterface\\Analytics\\RetentionInterface: '@App\\Service\\Analytics\\RetentionService'",
            "App\\ServiceInterface\\Analytics\\RollupInterface: '@App\\Service\\Analytics\\RollupService'",
            "App\\ServiceInterface\\Analytics\\SegmentationInterface: '@App\\Service\\Analytics\\SegmentationService'",
            "App\\ServiceInterface\\Analytics\\SloCalculatorInterface: '@App\\Service\\Analytics\\SloCalculator'",
            "App\\ServiceInterface\\Analytics\\TenantScopeInterface: '@App\\Service\\Analytics\\TenantScope'",
            "App\\ServiceInterface\\Analytics\\TransformerInterface: '@App\\Service\\Analytics\\Transformer'",
            "App\\ServiceInterface\\Analytics\\WindowQueryInterface: '@App\\Service\\Analytics\\WindowQuery'",
            "Psr\\Log\\LoggerInterface: '@Psr\\Log\\NullLogger'",
        ];

        foreach ($expected as $alias) {
            self::assertStringContainsString($alias, $yaml);
        }
    }
}
