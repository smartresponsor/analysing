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
            "App\Analysing\\ServiceInterface\\AnalyticsAccessGuardInterface: '@App\Analysing\\Service\\AnalyticsAccessGuard'",
            "App\Analysing\ServiceInterface\AnalyticsAggregateServiceInterface: '@App\Analysing\Service\AnalyticsAggregateService'",
            "App\Analysing\\ServiceInterface\\AnalyticsAnomalyDetectorInterface: '@App\Analysing\\Service\\AnalyticsAnomalyDetector'",
            "App\Analysing\\ServiceInterface\\AnalyticsCacheInterface: '@App\Analysing\\Service\\AnalyticsLocalCache'",
            "App\Analysing\\ServiceInterface\\AnalyticsCsvImportInterface: '@App\Analysing\\Service\\AnalyticsCsvImporter'",
            "App\Analysing\ServiceInterface\AnalyticsDashboardServiceInterface: '@App\Analysing\Service\AnalyticsDashboardService'",
            "App\Analysing\ServiceInterface\AnalyticsHealthServiceInterface: '@App\Analysing\Service\AnalyticsHealthService'",
            "App\Analysing\\ServiceInterface\\AnalyticsKpiRegistryInterface: '@App\Analysing\\Service\\AnalyticsKpiRegistry'",
            "App\Analysing\\ServiceInterface\\AnalyticsMetricIngestInterface: '@App\Analysing\Service\AnalyticsMetricIngestService'",
            "App\Analysing\\ServiceInterface\\AnalyticsNotifierInterface: '@App\Analysing\\Service\\AnalyticsWebhookNotifier'",
            "App\Analysing\\ServiceInterface\\AnalyticsReportBundleInterface: '@App\Analysing\\Service\\AnalyticsReportBundle'",
            "App\Analysing\\ServiceInterface\\AnalyticsRetentionInterface: '@App\Analysing\Service\AnalyticsRetentionService'",
            "App\Analysing\\ServiceInterface\\AnalyticsRollupInterface: '@App\Analysing\Service\AnalyticsRollupService'",
            "App\Analysing\\ServiceInterface\\AnalyticsSegmentationInterface: '@App\Analysing\Service\AnalyticsSegmentationService'",
            "App\Analysing\\ServiceInterface\\AnalyticsSloCalculatorInterface: '@App\Analysing\\Service\\AnalyticsSloCalculator'",
            "App\Analysing\\ServiceInterface\\AnalyticsVendorScopeInterface: '@App\Analysing\\Service\\AnalyticsVendorScope'",
            "App\Analysing\\ServiceInterface\\AnalyticsTransformerInterface: '@App\Analysing\\Service\\AnalyticsTransformer'",
            "App\Analysing\\ServiceInterface\\AnalyticsWindowQueryInterface: '@App\Analysing\\Service\\AnalyticsWindowQuery'",
            "Psr\\Log\\LoggerInterface: '@Psr\\Log\\NullLogger'",
        ];

        foreach ($expected as $alias) {
            self::assertStringContainsString($alias, $yaml);
        }
    }
}
