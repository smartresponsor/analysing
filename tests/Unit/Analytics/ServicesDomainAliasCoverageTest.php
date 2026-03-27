<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use PHPUnit\Framework\TestCase;

final class ServicesDomainAliasCoverageTest extends TestCase
{
    public function testAllAnalyticsDomainInterfacesAreAliasedInServicesYaml(): void
    {
        $yaml = (string) file_get_contents(__DIR__.'/../../../config/services.yaml');

        $expected = [
            "App\\DomainInterface\\Analytics\\AnalyticsInterface: '@App\\Domain\\Analytics\\Analytics'",
            "App\\DomainInterface\\Analytics\\ClickhouseClientInterface: '@App\\Domain\\Analytics\\ClickhouseClient'",
            "App\\DomainInterface\\Analytics\\ExperimentInterface: '@App\\Domain\\Analytics\\Experiment'",
            "App\\DomainInterface\\Analytics\\FlagInterface: '@App\\Domain\\Analytics\\Flag'",
            "App\\DomainInterface\\Analytics\\InsightInterface: '@App\\Domain\\Analytics\\Insight'",
            "App\\RepositoryInterface\\Analytics\\InfraRepositoryInterface: '@App\\Repository\\Analytics\\InfraRepository'",
        ];

        foreach ($expected as $alias) {
            self::assertStringContainsString($alias, $yaml);
        }
    }
}
