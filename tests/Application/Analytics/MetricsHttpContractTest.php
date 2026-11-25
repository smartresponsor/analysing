<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Application\Analytics;

use App\Analysing\RepositoryInterface\AnalyticsExportJobRepositoryInterface;
use App\Analysing\Tests\Application\Analytics\Support\AnalyticsContractAssertions;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Validates the metrics endpoint over the real Symfony HTTP layer.
 */
final class MetricsHttpContractTest extends WebTestCase
{
    use AnalyticsContractAssertions;

    protected static function getKernelClass(): string
    {
        return \App\Analysing\Kernel::class;
    }

    public function testMetricsEndpointMatchesContract(): void
    {
        $client = static::createClient(['environment' => 'test']);

        $repository = $this->createMock(AnalyticsExportJobRepositoryInterface::class);
        $repository->method('find')->willReturn(null);
        $repository->method('findRecent')->willReturn([]);
        static::getContainer()->set(AnalyticsExportJobRepositoryInterface::class, $repository);

        $client->request('GET', '/api/analytics/export/jobs/metrics');

        self::assertResponseStatusCodeSame(200);
        self::assertResponseHeaderSame('content-type', 'application/json');

        /** @var array<string,mixed> $payload */
        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertMetricsContract($payload);
    }
}
