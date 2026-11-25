<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Application\Analytics;

use App\Analysing\RepositoryInterface\AnalyticsExportJobRepositoryInterface;
use App\Analysing\Service\AnalyticsExportJobMetricsService;
use App\Analysing\Tests\Application\Analytics\Support\AnalyticsContractAssertions;
use App\Analysing\Tests\Fixtures\Factories\ExportJobFactory;
use PHPUnit\Framework\TestCase;

/**
 * Validates metrics snapshot contract independently from transport layer.
 */
final class MetricsContractTest extends TestCase
{
    use AnalyticsContractAssertions;

    public function testMetricsContract(): void
    {
        $jobs = [
            ExportJobFactory::completed(),
            ExportJobFactory::failed(),
            ExportJobFactory::pending(),
        ];

        $repository = $this->createMock(AnalyticsExportJobRepositoryInterface::class);
        $repository->method('findRecent')->willReturn($jobs);

        $service = new AnalyticsExportJobMetricsService($repository);
        $payload = $service->snapshot();

        $this->assertMetricsContract($payload);
    }
}
