<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Service\AnalyticsHealthService;
use App\Analysing\ServiceInterface\AnalyticsKpiRegistryInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class HealthServiceStorageMetadataTest extends TestCase
{
    public function testStatusExposesStorageMetadata(): void
    {
        $registry = $this->createMock(AnalyticsKpiRegistryInterface::class);
        $registry->method('list')->willReturn(['orders_per_day' => 'Orders created per day']);

        $service = new AnalyticsHealthService(new NullLogger(), $registry, 'embedded_sample', 'sample', true);
        $status = $service->status();

        self::assertSame('embedded_sample', $status['storage_driver']);
        self::assertSame('sample', $status['storage_mode']);
        self::assertTrue($status['storage_available']);
        self::assertSame('php bin/console analytics:storage:prepare --seed', $status['storage_prepare_command']);
        self::assertContains('aggregate_funnel_daily', $status['storage_required_tables']);
    }
}
