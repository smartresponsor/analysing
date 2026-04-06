<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Analytics;

use App\Controller\Analytics\ExportJobController;
use App\Entity\Analytics\ExportJob;
use App\Service\Analytics\ExportJobMetricsService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;

final class ExportJobControllerTest extends TestCase
{
    public function testStatusReturns404WhenJobMissing(): void
    {
        $repo = $this->createMock(ObjectRepository::class);
        $repo->method('find')->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repo);

        $metrics = $this->createMock(ExportJobMetricsService::class);

        $controller = new ExportJobController($em, $metrics);
        $response = $controller->status(1);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(404, $response->getStatusCode());
    }

    public function testStatusReturnsJobData(): void
    {
        $job = new ExportJob('csv', ['export_path' => '/tmp/file.csv']);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->method('find')->willReturn($job);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repo);

        $metrics = $this->createMock(ExportJobMetricsService::class);

        $controller = new ExportJobController($em, $metrics);
        $response = $controller->status(1);

        self::assertSame(200, $response->getStatusCode());
    }

    public function testMetricsReturnsSnapshot(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $metrics = $this->createMock(ExportJobMetricsService::class);
        $metrics->method('snapshot')->willReturn(['pending' => 1, 'failed' => 0]);

        $controller = new ExportJobController($em, $metrics);
        $response = $controller->metrics();

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('{"pending":1,"failed":0}', $response->getContent());
    }
}
