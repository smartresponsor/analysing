<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Controller\Analytics;

use App\Analysing\Controller\AnalyticsExportJobController;
use App\Analysing\Entity\Analytics\AnalyticsExportJobEntity;
use App\Analysing\RepositoryInterface\AnalyticsExportJobRepositoryInterface;
use App\Analysing\Service\AnalyticsExportJobMetricsService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;

final class ExportJobControllerTest extends TestCase
{
    public function testStatusReturns404WhenJobMissing(): void
    {
        $jobs = $this->createMock(AnalyticsExportJobRepositoryInterface::class);
        $jobs->method('find')->willReturn(null);
        $jobs->method('findRecent')->willReturn([]);

        $metrics = new AnalyticsExportJobMetricsService($jobs);

        $controller = new AnalyticsExportJobController($jobs, $metrics);
        $response = $controller->status(1);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(404, $response->getStatusCode());
    }

    public function testStatusReturnsJobData(): void
    {
        $job = new AnalyticsExportJobEntity('csv', ['export_path' => '/tmp/file.csv']);

        $jobs = $this->createMock(AnalyticsExportJobRepositoryInterface::class);
        $jobs->method('find')->willReturn($job);
        $jobs->method('findRecent')->willReturn([]);

        $metrics = new AnalyticsExportJobMetricsService($jobs);

        $controller = new AnalyticsExportJobController($jobs, $metrics);
        $response = $controller->status(1);

        self::assertSame(200, $response->getStatusCode());
    }

    public function testMetricsReturnsSnapshot(): void
    {
        $jobs = $this->createMock(AnalyticsExportJobRepositoryInterface::class);
        $jobs->method('findRecent')->willReturn([]);

        $metrics = new AnalyticsExportJobMetricsService($jobs);

        $controller = new AnalyticsExportJobController($jobs, $metrics);
        $response = $controller->metrics();

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(
            '{"jobs_total":0,"status_counts":{"pending":0,"running":0,"done":0,"failed":0},"retryable_failed_jobs":0,"avg_duration_ms":0,"exported_rows_total":0}',
            $response->getContent()
        );
    }
}
