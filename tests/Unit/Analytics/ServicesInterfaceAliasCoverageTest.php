<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use PHPUnit\Framework\TestCase;

final class ServicesInterfaceAliasCoverageTest extends TestCase
{
    public function testCanonicalInterfacesAreAliasedInServicesYaml(): void
    {
        $yaml = (string) file_get_contents(__DIR__.'/../../../config/component/services.yaml');

        $expected = [
            "App\Analysing\\ServiceInterface\\AnalyticsInterface: '@App\Analysing\\Service\\Analytics'",
            "App\Analysing\\ServiceInterface\\AnalyticsClickhouseClientInterface: '@App\Analysing\\Service\\AnalyticsClickhouseClient'",
            "App\Analysing\\ServiceInterface\\AnalyticsExperimentInterface: '@App\Analysing\\Service\\AnalyticsExperiment'",
            "App\Analysing\\ServiceInterface\\AnalyticsFlagInterface: '@App\Analysing\\Service\\AnalyticsFlag'",
            "App\Analysing\\ServiceInterface\\AnalyticsInsightInterface: '@App\Analysing\\Service\\AnalyticsInsight'",
            "App\Analysing\RepositoryInterface\AnalyticsRepositoryInterface: '@App\Analysing\Repository\AnalyticsRepository'",
        ];

        foreach ($expected as $alias) {
            self::assertStringContainsString($alias, $yaml);
        }
    }
}
