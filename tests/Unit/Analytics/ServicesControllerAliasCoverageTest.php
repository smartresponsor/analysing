<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use PHPUnit\Framework\TestCase;

final class ServicesControllerAliasCoverageTest extends TestCase
{
    public function testAllAnalyticsControllerInterfacesAreAliasedInServicesYaml(): void
    {
        $yaml = (string) file_get_contents(__DIR__.'/../../../config/services.yaml');

        $expected = [
            "App\\ControllerInterface\\Analytics\\AggregateControllerInterface: '@App\\Controller\\Analytics\\AggregateController'",
            "App\\ControllerInterface\\Analytics\\AnalyticsControllerInterface: '@App\\Controller\\Analytics\\AnalyticsController'",
            "App\\ControllerInterface\\Analytics\\ApiControllerInterface: '@App\\Controller\\Analytics\\ApiController'",
            "App\\ControllerInterface\\Analytics\\DashboardControllerInterface: '@App\\Controller\\Analytics\\DashboardController'",
            "App\\ControllerInterface\\Analytics\\DashboardPageControllerInterface: '@App\\Controller\\Analytics\\DashboardPageController'",
            "App\\ControllerInterface\\Analytics\\ExperimentControllerInterface: '@App\\Controller\\Analytics\\ExperimentController'",
            "App\\ControllerInterface\\Analytics\\FlagControllerInterface: '@App\\Controller\\Analytics\\FlagController'",
            "App\\ControllerInterface\\Analytics\\HealthControllerInterface: '@App\\Controller\\Analytics\\HealthController'",
            "App\\ControllerInterface\\Analytics\\IngestControllerInterface: '@App\\Controller\\Analytics\\IngestController'",
            "App\\ControllerInterface\\Analytics\\InsightControllerInterface: '@App\\Controller\\Analytics\\InsightController'",
        ];

        foreach ($expected as $alias) {
            self::assertStringContainsString($alias, $yaml);
        }
    }
}
