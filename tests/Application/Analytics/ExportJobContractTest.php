<?php

declare(strict_types=1);

namespace App\Tests\Application\Analytics;

use App\Service\Analytics\ExportJobView;
use App\Tests\Application\Analytics\Support\AnalyticsContractAssertions;
use App\Tests\Fixtures\Factories\ExportJobFactory;
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
        $payload = ExportJobView::toArray($job);

        $this->assertExportJobContract($payload);
    }

    public function testPendingJobContract(): void
    {
        $job = ExportJobFactory::pending();
        $payload = ExportJobView::toArray($job);

        $this->assertExportJobContract($payload);
    }

    public function testFailedJobContract(): void
    {
        $job = ExportJobFactory::failed();
        $payload = ExportJobView::toArray($job);

        $this->assertExportJobContract($payload);
    }
}
