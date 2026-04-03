<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\Service\Analytics\HealthService;
use App\ServiceInterface\Analytics\KpiRegistryInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class HealthServiceStorageMetadataTest extends TestCase
{
    public function testStatusExposesStorageMetadata(): void
    {
        $registry = $this->createMock(KpiRegistryInterface::class);
        $registry->method('list')->willReturn(['orders_per_day' => 'Orders created per day']);

        $service = new HealthService(new NullLogger(), $registry, 'embedded_sample', 'sample', true);
        $status = $service->status();

        self::assertSame('embedded_sample', $status['storage_driver']);
        self::assertSame('sample', $status['storage_mode']);
        self::assertTrue($status['storage_available']);
    }
}
