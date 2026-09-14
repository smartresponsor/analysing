<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Service\AnalyticsHealthService;
use App\Analysing\ServiceInterface\AnalyticsKpiRegistryInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class HealthServiceTest extends TestCase
{
    public function testStatusIncludesCatalogMetadata(): void
    {
        $registry = $this->createMock(AnalyticsKpiRegistryInterface::class);
        $registry->method('list')->willReturn([
            ['key' => 'revenue', 'label' => 'Revenue'],
        ]);

        $service = new AnalyticsHealthService(new NullLogger(), $registry);
        $status = $service->status();

        self::assertTrue($status['ok']);
        self::assertSame('analytics', $status['component']);
        self::assertSame(1, $status['kpi_catalog_count']);
        self::assertNotNull($status['kpi_catalog_checksum']);
        self::assertArrayHasKey('duration_ms', $status);
    }

    public function testStatusHandlesRegistryRuntimeFailure(): void
    {
        $registry = $this->createMock(AnalyticsKpiRegistryInterface::class);
        $registry->method('list')->willThrowException(new \RuntimeException('catalog down'));

        $service = new AnalyticsHealthService(new NullLogger(), $registry);
        $status = $service->status();

        self::assertFalse($status['ok']);
        self::assertSame(0, $status['kpi_catalog_count']);
        self::assertNull($status['kpi_catalog_checksum']);
    }
}
