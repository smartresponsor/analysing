<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\Service\Analytics\HealthService;
use App\Service\Http\TenantContext;
use App\ServiceInterface\Analytics\KpiRegistryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class HealthServiceTenantMetadataTest extends TestCase
{
    public function testStatusIncludesTenantMetadata(): void
    {
        /** @var KpiRegistryInterface&MockObject $registry */
        $registry = $this->createMock(KpiRegistryInterface::class);
        $registry->method('list')->willReturn([
            ['key' => 'gross_minor'],
        ]);

        $tenantContext = new TenantContext();
        $tenantContext->set('acme');

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
            true,
            $tenantContext,
        );

        $status = $service->status();

        self::assertTrue($status['tenant_context_enabled']);
        self::assertSame('acme', $status['tenant']);
    }
}
