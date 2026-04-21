<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Controller\Analytics;

use App\Analysing\Controller\Analytics\ExportJobController;
use App\Analysing\Entity\Analytics\ExportJob;
use App\Analysing\Service\Analytics\ExportJobMetricsService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;

final class ExportJobControllerTest extends TestCase
{
    public function testStatusReturns404WhenJobMissing(): void
    {
        $repo = $this->createMock(EntityRepository::class);
        $repo->method('find')->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repo);

        $metrics = new ExportJobMetricsService($em);

        $controller = new ExportJobController($em, $metrics);
        $response = $controller->status(1);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(404, $response->getStatusCode());
    }

    public function testStatusReturnsJobData(): void
    {
        $job = new ExportJob('csv', ['export_path' => '/tmp/file.csv']);

        $repo = $this->createMock(EntityRepository::class);
        $repo->method('find')->willReturn($job);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repo);

        $metrics = new ExportJobMetricsService($em);

        $controller = new ExportJobController($em, $metrics);
        $response = $controller->status(1);

        self::assertSame(200, $response->getStatusCode());
    }

    public function testMetricsReturnsSnapshot(): void
    {
        $repo = $this->createMock(EntityRepository::class);
        $repo->method('findBy')->willReturn([]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repo);

        $metrics = new ExportJobMetricsService($em);

        $controller = new ExportJobController($em, $metrics);
        $response = $controller->metrics();

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(
            '{"jobs_total":0,"status_counts":{"pending":0,"running":0,"done":0,"failed":0},"retryable_failed_jobs":0,"avg_duration_ms":0,"exported_rows_total":0}',
            $response->getContent()
        );
    }
}
