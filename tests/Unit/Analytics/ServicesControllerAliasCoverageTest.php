<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use PHPUnit\Framework\TestCase;

final class ServicesControllerAliasCoverageTest extends TestCase
{
    public function testAllAnalyticsControllerInterfacesAreAliasedInServicesYaml(): void
    {
        $yaml = (string) file_get_contents(__DIR__.'/../../../config/component/services.yaml');

        $expected = [
            "App\Analysing\Controller\AnalyticsAggregateControllerInterface: '@App\Analysing\Controller\AnalyticsAggregateController'",
            "App\Analysing\Controller\AnalyticsControllerInterface: '@App\Analysing\Controller\AnalyticsController'",
            "App\Analysing\Controller\AnalyticsApiControllerInterface: '@App\Analysing\Controller\AnalyticsApiController'",
            "App\Analysing\Controller\AnalyticsDashboardControllerInterface: '@App\Analysing\Controller\AnalyticsDashboardController'",
            "App\Analysing\Controller\AnalyticsDashboardPageControllerInterface: '@App\Analysing\Controller\AnalyticsDashboardPageController'",
            "App\Analysing\Controller\AnalyticsExperimentControllerInterface: '@App\Analysing\Controller\AnalyticsExperimentController'",
            "App\Analysing\Controller\AnalyticsFlagControllerInterface: '@App\Analysing\Controller\AnalyticsFlagController'",
            "App\Analysing\Controller\AnalyticsHealthControllerInterface: '@App\Analysing\Controller\AnalyticsHealthController'",
            "App\Analysing\Controller\AnalyticsIngestControllerInterface: '@App\Analysing\Controller\AnalyticsIngestController'",
            "App\Analysing\Controller\AnalyticsInsightControllerInterface: '@App\Analysing\Controller\AnalyticsInsightController'",
        ];

        foreach ($expected as $alias) {
            self::assertStringContainsString($alias, $yaml);
        }
    }
}
