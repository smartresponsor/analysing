<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Service\AnalyticsHealthService;
use App\Analysing\ServiceInterface\AnalyticsKpiRegistryInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class HealthServiceIdempotencyMetadataTest extends TestCase
{
    public function testStatusIncludesIdempotencyMetadata(): void
    {
        $registry = $this->createMock(AnalyticsKpiRegistryInterface::class);
        $registry->method('list')->willReturn([]);

        $service = new AnalyticsHealthService(
            new NullLogger(),
            $registry,
            'embedded_sample',
            'sample',
            true,
            true,
            'local_file',
            false,
        );

        $status = $service->status();

        self::assertArrayHasKey('idempotency_enabled', $status);
        self::assertArrayHasKey('idempotency_mode', $status);
        self::assertArrayHasKey('idempotency_required', $status);
        self::assertTrue($status['idempotency_enabled']);
        self::assertSame('local_file', $status['idempotency_mode']);
        self::assertFalse($status['idempotency_required']);
    }
}
