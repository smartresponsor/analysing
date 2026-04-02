<?php

declare(strict_types=1);

namespace App\Tests\Application\Analytics;

use App\Controller\Analytics\ExportJobController;
use App\Entity\Analytics\ExportJob;
use App\Service\Analytics\ExportJobMetricsService;
use App\Tests\Application\Analytics\Support\AnalyticsContractAssertions;
use App\Tests\Fixtures\Factories\ExportJobFactory;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Validates controller-level response contracts for analytics export job endpoints.
 *
 * These tests intentionally exercise the HTTP-facing controller layer without assuming a specific
 * Symfony kernel bootstrap configuration. This keeps the contract suite executable even before
 * WebTestCase or Panther infrastructure is wired into the repository.
 */
final class ExportJobControllerContractTest extends TestCase
{
    use AnalyticsContractAssertions;

    public function testStatusReturnsExportJobContract(): void
    {
        $job = ExportJobFactory::completed();
        $controller = new ExportJobController(
            $this->createEntityManagerReturning($job),
            $this->createMetricsServiceStub()
        );

        $response = $controller->status(1);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(200, $response->getStatusCode());

        /** @var array<string,mixed> $payload */
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertExportJobContract($payload);
    }

    public function testMetricsReturnsMetricsContract(): void
    {
        $controller = new ExportJobController(
            $this->createEntityManagerReturning(null),
            $this->createMetricsServiceStub()
        );

        $response = $controller->metrics();

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(200, $response->getStatusCode());

        /** @var array<string,mixed> $payload */
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertMetricsContract($payload);
    }

    private function createEntityManagerReturning(?ExportJob $job): EntityManagerInterface
    {
        /** @var ObjectRepository&MockObject $repository */
        $repository = $this->createMock(ObjectRepository::class);
        $repository->method('find')->willReturn($job);

        /** @var EntityManagerInterface&MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->with(ExportJob::class)->willReturn($repository);

        return $em;
    }

    private function createMetricsServiceStub(): ExportJobMetricsService
    {
        /** @var ObjectRepository&MockObject $repository */
        $repository = $this->createMock(ObjectRepository::class);
        $repository->method('findBy')->willReturn([
            ExportJobFactory::completed(),
            ExportJobFactory::failed(),
            ExportJobFactory::pending(),
        ]);

        /** @var EntityManagerInterface&MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->with(ExportJob::class)->willReturn($repository);

        return new ExportJobMetricsService($em);
    }
}
