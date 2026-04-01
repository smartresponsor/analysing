<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Analytics;

use App\Controller\Analytics\ExportJobController;
use App\Entity\Analytics\ExportJob;
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

        $controller = new ExportJobController($em);
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

        $controller = new ExportJobController($em);
        $response = $controller->status(1);

        self::assertSame(200, $response->getStatusCode());
    }
}
