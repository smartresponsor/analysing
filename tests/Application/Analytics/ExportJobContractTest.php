<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Application\Analytics;

use App\Analysing\Service\AnalyticsExportJobView;
use App\Analysing\Tests\Application\Analytics\Support\AnalyticsContractAssertions;
use App\Analysing\Tests\Fixtures\Factories\ExportJobFactory;
use PHPUnit\Framework\TestCase;

/**
 * Validates the export job API contract using the view layer as source of truth.
 */
final class ExportJobContractTest extends TestCase
{
    use AnalyticsContractAssertions;

    public function testCompletedJobContract(): void
    {
        $job = ExportJobFactory::completed();
        $payload = AnalyticsExportJobView::toArray($job);

        $this->assertExportJobContract($payload);
    }

    public function testPendingJobContract(): void
    {
        $job = ExportJobFactory::pending();
        $payload = AnalyticsExportJobView::toArray($job);

        $this->assertExportJobContract($payload);
    }

    public function testFailedJobContract(): void
    {
        $job = ExportJobFactory::failed();
        $payload = AnalyticsExportJobView::toArray($job);

        $this->assertExportJobContract($payload);
    }
}
