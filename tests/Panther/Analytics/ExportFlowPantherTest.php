<?php

declare(strict_types=1);

namespace App\Tests\Panther\Analytics;

use PHPUnit\Framework\TestCase;

/**
 * Browser-level smoke coverage for analytics endpoints.
 *
 * This test is intentionally resilient: it only executes when Panther is installed and when a
 * reachable base URI is available. This allows the repository to adopt Panther incrementally
 * without breaking environments that have not yet installed `symfony/panther`.
 */
final class ExportFlowPantherTest extends TestCase
{
    public function testMetricsEndpointIsReachableInBrowserContext(): void
    {
        $clientClass = '\\Symfony\\Component\\Panther\\Client';
        if (!class_exists($clientClass)) {
            self::markTestSkipped('Panther is not installed.');
        }

        if (!method_exists($clientClass, 'createChromeClient')) {
            self::markTestSkipped('Panther Chrome client factory is unavailable.');
        }

        $baseUri = (string) ($_SERVER['PANTHER_EXTERNAL_BASE_URI'] ?? getenv('PANTHER_EXTERNAL_BASE_URI') ?: 'http://127.0.0.1:8000');
        $headless = (bool) ($_SERVER['PANTHER_HEADLESS'] ?? getenv('PANTHER_HEADLESS') ?: true);
        $arguments = $headless ? ['--headless=new'] : [];

        /** @var object $client */
        $client = $clientClass::createChromeClient(null, $arguments, [], $baseUri);
        $client->request('GET', '/api/analytics/export-jobs/metrics');

        if (method_exists($client, 'getPageSource')) {
            $content = (string) $client->getPageSource();
            self::assertStringContainsString('jobs_total', $content);
            self::assertStringContainsString('status_counts', $content);

            return;
        }

        self::markTestSkipped('Panther client does not expose page source accessor.');
    }
}
