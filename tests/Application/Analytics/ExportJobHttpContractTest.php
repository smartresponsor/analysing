<?php

declare(strict_types=1);

namespace App\Tests\Application\Analytics;

use App\Tests\Application\Analytics\Support\AnalyticsContractAssertions;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Validates export job endpoint contract over real HTTP layer.
 */
final class ExportJobHttpContractTest extends WebTestCase
{
    use AnalyticsContractAssertions;

    public function testNonExistingJobReturns404(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/analytics/export-jobs/999999');

        self::assertResponseStatusCodeSame(404);
    }
}
