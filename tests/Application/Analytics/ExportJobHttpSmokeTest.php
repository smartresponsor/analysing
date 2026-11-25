<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Application\Analytics;

use App\Analysing\RepositoryInterface\AnalyticsExportJobRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * First real HTTP smoke test for analytics endpoints.
 *
 * This test validates that the Symfony HTTP layer is operational and that
 * analytics endpoints respond successfully.
 */
final class ExportJobHttpSmokeTest extends WebTestCase
{
    protected static function getKernelClass(): string
    {
        return \App\Analysing\Kernel::class;
    }

    public function testMetricsEndpointResponds(): void
    {
        $client = static::createClient(['environment' => 'test']);

        $repository = $this->createMock(AnalyticsExportJobRepositoryInterface::class);
        $repository->method('find')->willReturn(null);
        $repository->method('findRecent')->willReturn([]);
        static::getContainer()->set(AnalyticsExportJobRepositoryInterface::class, $repository);

        $client->request('GET', '/api/analytics/export/jobs/metrics');

        self::assertResponseStatusCodeSame(200);
        self::assertResponseHeaderSame('content-type', 'application/json');
    }
}
