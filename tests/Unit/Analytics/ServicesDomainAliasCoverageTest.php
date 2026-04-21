<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use PHPUnit\Framework\TestCase;

final class ServicesDomainAliasCoverageTest extends TestCase
{
    public function testAllAnalyticsDomainInterfacesAreAliasedInServicesYaml(): void
    {
        $yaml = (string) file_get_contents(__DIR__.'/../../../config/component/services.yaml');

        $expected = [
            "App\Analysing\\DomainInterface\\Analytics\\AnalyticsInterface: '@App\Analysing\\Domain\\Analytics\\Analytics'",
            "App\Analysing\\DomainInterface\\Analytics\\ClickhouseClientInterface: '@App\Analysing\\Domain\\Analytics\\ClickhouseClient'",
            "App\Analysing\\DomainInterface\\Analytics\\ExperimentInterface: '@App\Analysing\\Domain\\Analytics\\Experiment'",
            "App\Analysing\\DomainInterface\\Analytics\\FlagInterface: '@App\Analysing\\Domain\\Analytics\\Flag'",
            "App\Analysing\\DomainInterface\\Analytics\\InsightInterface: '@App\Analysing\\Domain\\Analytics\\Insight'",
            "App\Analysing\\RepositoryInterface\\Analytics\\InfraRepositoryInterface: '@App\Analysing\\Repository\\Analytics\\InfraRepository'",
        ];

        foreach ($expected as $alias) {
            self::assertStringContainsString($alias, $yaml);
        }
    }
}
