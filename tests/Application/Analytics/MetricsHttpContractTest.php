<?php

declare(strict_types=1);

namespace App\Tests\Application\Analytics;

use App\Tests\Application\Analytics\Support\AnalyticsContractAssertions;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Validates the metrics endpoint over the real Symfony HTTP layer.
 */
final class MetricsHttpContractTest extends WebTestCase
{
    use AnalyticsContractAssertions;

    public function testMetricsEndpointMatchesContract(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/analytics/export-jobs/metrics');

        self::assertResponseStatusCodeSame(200);
        self::assertResponseHeaderSame('content-type', 'application/json');

        /** @var array<string,mixed> $payload */
        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertMetricsContract($payload);
    }
}
