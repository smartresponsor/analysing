<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\Service\Analytics\HealthService;
use App\ServiceInterface\Analytics\KpiRegistryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class HealthServiceRateLimitMetadataTest extends TestCase
{
    public function testStatusIncludesRateLimitMetadata(): void
    {
        /** @var KpiRegistryInterface&MockObject $registry */
        $registry = $this->createMock(KpiRegistryInterface::class);
        $registry->method('list')->willReturn([
            ['key' => 'gross_minor'],
        ]);

        $service = new HealthService(
            new NullLogger(),
            $registry,
            'embedded_sample',
            'sample',
            true,
            true,
            'local_file',
            false,
            true,
            true,
            true,
            'local_file',
            60,
            5,
        );

        $status = $service->status();

        self::assertTrue($status['rate_limit_enabled']);
        self::assertSame('local_file', $status['rate_limit_mode']);
        self::assertSame(60, $status['rate_limit_window_seconds']);
        self::assertSame(5, $status['rate_limit_default_write_limit']);
    }
}
