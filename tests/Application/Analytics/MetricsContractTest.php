<?php

declare(strict_types=1);

namespace App\Tests\Application\Analytics;

use App\Entity\Analytics\ExportJob;
use App\Service\Analytics\ExportJobMetricsService;
use App\Tests\Application\Analytics\Support\AnalyticsContractAssertions;
use App\Tests\Fixtures\Factories\ExportJobFactory;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\MockObject\MockObject;
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

        /** @var ObjectRepository&MockObject $repository */
        $repository = $this->createMock(ObjectRepository::class);
        $repository->method('findBy')->willReturn($jobs);

        /** @var EntityManagerInterface&MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->with(ExportJob::class)->willReturn($repository);

        $service = new ExportJobMetricsService($em);
        $payload = $service->snapshot();

        $this->assertMetricsContract($payload);
    }
}
