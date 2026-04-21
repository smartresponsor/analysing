<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use PHPUnit\Framework\TestCase;

final class ServiceAliasConfigTest extends TestCase
{
    public function testCriticalServiceAliasesExistInServicesYaml(): void
    {
        $yaml = (string) file_get_contents(__DIR__.'/../../../config/component/services.yaml');

        $expectedAliases = [
            "App\Analysing\\DomainInterface\\Analytics\\AnalyticsInterface: '@App\Analysing\\Domain\\Analytics\\Analytics'",
            "App\Analysing\\DomainInterface\\Analytics\\ClickhouseClientInterface: '@App\Analysing\\Domain\\Analytics\\ClickhouseClient'",
            "App\Analysing\\RepositoryInterface\\Analytics\\InfraRepositoryInterface: '@App\Analysing\\Repository\\Analytics\\InfraRepository'",
            "App\Analysing\\ServiceInterface\\Analytics\\DashboardServiceInterface: '@App\Analysing\\Service\\Analytics\\DashboardService'",
            "App\Analysing\\ServiceInterface\\Analytics\\ReportGeneratorServiceInterface: '@App\Analysing\\Service\\Analytics\\ReportGeneratorService'",
            "Psr\\Log\\LoggerInterface: '@Psr\\Log\\NullLogger'",
        ];

        foreach ($expectedAliases as $alias) {
            self::assertStringContainsString($alias, $yaml);
        }
    }
}
