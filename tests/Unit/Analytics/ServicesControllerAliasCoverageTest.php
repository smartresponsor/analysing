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
            "App\Analysing\\ControllerInterface\\Analytics\\AggregateControllerInterface: '@App\Analysing\\Controller\\Analytics\\AggregateController'",
            "App\Analysing\\ControllerInterface\\Analytics\\AnalyticsControllerInterface: '@App\Analysing\\Controller\\Analytics\\AnalyticsController'",
            "App\Analysing\\ControllerInterface\\Analytics\\ApiControllerInterface: '@App\Analysing\\Controller\\Analytics\\ApiController'",
            "App\Analysing\\ControllerInterface\\Analytics\\DashboardControllerInterface: '@App\Analysing\\Controller\\Analytics\\DashboardController'",
            "App\Analysing\\ControllerInterface\\Analytics\\DashboardPageControllerInterface: '@App\Analysing\\Controller\\Analytics\\DashboardPageController'",
            "App\Analysing\\ControllerInterface\\Analytics\\ExperimentControllerInterface: '@App\Analysing\\Controller\\Analytics\\ExperimentController'",
            "App\Analysing\\ControllerInterface\\Analytics\\FlagControllerInterface: '@App\Analysing\\Controller\\Analytics\\FlagController'",
            "App\Analysing\\ControllerInterface\\Analytics\\HealthControllerInterface: '@App\Analysing\\Controller\\Analytics\\HealthController'",
            "App\Analysing\\ControllerInterface\\Analytics\\IngestControllerInterface: '@App\Analysing\\Controller\\Analytics\\IngestController'",
            "App\Analysing\\ControllerInterface\\Analytics\\InsightControllerInterface: '@App\Analysing\\Controller\\Analytics\\InsightController'",
        ];

        foreach ($expected as $alias) {
            self::assertStringContainsString($alias, $yaml);
        }
    }
}
