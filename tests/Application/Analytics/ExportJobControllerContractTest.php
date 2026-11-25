<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Application\Analytics;

use App\Analysing\Controller\AnalyticsExportJobController;
use App\Analysing\Entity\Analytics\AnalyticsExportJobEntity;
use App\Analysing\RepositoryInterface\AnalyticsExportJobRepositoryInterface;
use App\Analysing\Service\AnalyticsExportJobMetricsService;
use App\Analysing\Tests\Application\Analytics\Support\AnalyticsContractAssertions;
use App\Analysing\Tests\Fixtures\Factories\ExportJobFactory;
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
        $controller = new AnalyticsExportJobController(
            $this->createRepositoryReturning($job),
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
        $controller = new AnalyticsExportJobController(
            $this->createRepositoryReturning(null),
            $this->createMetricsServiceStub()
        );

        $response = $controller->metrics();

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(200, $response->getStatusCode());

        /** @var array<string,mixed> $payload */
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertMetricsContract($payload);
    }

    private function createRepositoryReturning(?AnalyticsExportJobEntity $job): AnalyticsExportJobRepositoryInterface
    {
        $repository = $this->createMock(AnalyticsExportJobRepositoryInterface::class);
        $repository->method('find')->willReturn($job);

        return $repository;
    }

    private function createMetricsServiceStub(): AnalyticsExportJobMetricsService
    {
        $repository = $this->createMock(AnalyticsExportJobRepositoryInterface::class);
        $repository->method('findRecent')->willReturn([
            ExportJobFactory::completed(),
            ExportJobFactory::failed(),
            ExportJobFactory::pending(),
        ]);

        return new AnalyticsExportJobMetricsService($repository);
    }
}
