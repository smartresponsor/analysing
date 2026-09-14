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
            "App\Analysing\\ServiceInterface\\AnalyticsInterface: '@App\Analysing\\Service\\Analytics'",
            "App\Analysing\\ServiceInterface\\AnalyticsClickhouseClientInterface: '@App\Analysing\\Service\\AnalyticsClickhouseClient'",
            "App\Analysing\RepositoryInterface\AnalyticsRepositoryInterface: '@App\Analysing\Repository\AnalyticsRepository'",
            "App\Analysing\ServiceInterface\AnalyticsDashboardServiceInterface: '@App\Analysing\Service\AnalyticsDashboardService'",
            "App\Analysing\ServiceInterface\AnalyticsReportGeneratorServiceInterface: '@App\Analysing\Service\AnalyticsReportGeneratorService'",
            "Psr\\Log\\LoggerInterface: '@Psr\\Log\\NullLogger'",
        ];

        foreach ($expectedAliases as $alias) {
            self::assertStringContainsString($alias, $yaml);
        }
    }
}
