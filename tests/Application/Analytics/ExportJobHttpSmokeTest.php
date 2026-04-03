<?php

declare(strict_types=1);

namespace App\Tests\Application\Analytics;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * First real HTTP smoke test for analytics endpoints.
 *
 * This test validates that the Symfony HTTP layer is operational and that
 * analytics endpoints respond successfully.
 */
final class ExportJobHttpSmokeTest extends WebTestCase
{
    public function testMetricsEndpointResponds(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/analytics/export-jobs/metrics');

        self::assertResponseStatusCodeSame(200);
        self::assertResponseHeaderSame('content-type', 'application/json');
    }
}
