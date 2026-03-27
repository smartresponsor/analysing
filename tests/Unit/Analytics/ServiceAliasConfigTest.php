<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use PHPUnit\Framework\TestCase;

final class ServiceAliasConfigTest extends TestCase
{
    public function testCriticalServiceAliasesExistInServicesYaml(): void
    {
        $yaml = (string) file_get_contents(__DIR__.'/../../../config/services.yaml');

        $expectedAliases = [
            "App\\DomainInterface\\Analytics\\AnalyticsInterface: '@App\\Domain\\Analytics\\Analytics'",
            "App\\DomainInterface\\Analytics\\ClickhouseClientInterface: '@App\\Domain\\Analytics\\ClickhouseClient'",
            "App\\RepositoryInterface\\Analytics\\InfraRepositoryInterface: '@App\\Repository\\Analytics\\InfraRepository'",
            "App\\ServiceInterface\\Analytics\\DashboardServiceInterface: '@App\\Service\\Analytics\\DashboardService'",
            "App\\ServiceInterface\\Analytics\\ReportGeneratorServiceInterface: '@App\\Service\\Analytics\\ReportGeneratorService'",
            "Psr\\Log\\LoggerInterface: '@Psr\\Log\\NullLogger'",
        ];

        foreach ($expectedAliases as $alias) {
            self::assertStringContainsString($alias, $yaml);
        }
    }
}
