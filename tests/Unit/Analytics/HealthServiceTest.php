<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\Service\Analytics\HealthService;
use App\ServiceInterface\Analytics\KpiRegistryInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class HealthServiceTest extends TestCase
{
    public function testStatusIncludesCatalogMetadata(): void
    {
        $registry = $this->createMock(KpiRegistryInterface::class);
        $registry->method('list')->willReturn([
            ['key' => 'revenue', 'label' => 'Revenue'],
        ]);

        $service = new HealthService(new NullLogger(), $registry);
        $status = $service->status();

        self::assertTrue($status['ok']);
        self::assertSame('analytics', $status['component']);
        self::assertSame(1, $status['kpi_catalog_count']);
        self::assertNotNull($status['kpi_catalog_checksum']);
        self::assertArrayHasKey('duration_ms', $status);
    }

    public function testStatusHandlesRegistryRuntimeFailure(): void
    {
        $registry = $this->createMock(KpiRegistryInterface::class);
        $registry->method('list')->willThrowException(new \RuntimeException('catalog down'));

        $service = new HealthService(new NullLogger(), $registry);
        $status = $service->status();

        self::assertFalse($status['ok']);
        self::assertSame(0, $status['kpi_catalog_count']);
        self::assertNull($status['kpi_catalog_checksum']);
    }
}
