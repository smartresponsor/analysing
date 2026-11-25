<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Application\Analytics;

use App\Analysing\RepositoryInterface\AnalyticsExportJobRepositoryInterface;
use App\Analysing\Tests\Application\Analytics\Support\AnalyticsContractAssertions;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Validates export job endpoint contract over real HTTP layer.
 */
final class ExportJobHttpContractTest extends WebTestCase
{
    use AnalyticsContractAssertions;

    protected static function getKernelClass(): string
    {
        return \App\Analysing\Kernel::class;
    }

    public function testNonExistingJobReturns404(): void
    {
        $client = static::createClient(['environment' => 'test']);

        $repository = $this->createMock(AnalyticsExportJobRepositoryInterface::class);
        $repository->method('find')->willReturn(null);
        $repository->method('findRecent')->willReturn([]);
        static::getContainer()->set(AnalyticsExportJobRepositoryInterface::class, $repository);

        $client->request('GET', '/api/analytics/export/jobs/999999');

        self::assertResponseStatusCodeSame(404);
    }
}
