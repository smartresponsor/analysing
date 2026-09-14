<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Service\AnalyticsHealthService;
use App\Analysing\Service\Http\AnalyticsVendorContext;
use App\Analysing\ServiceInterface\AnalyticsKpiRegistryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class HealthServiceVendorMetadataTest extends TestCase
{
    public function testStatusIncludesVendorMetadata(): void
    {
        /** @var AnalyticsKpiRegistryInterface&MockObject $registry */
        $registry = $this->createMock(AnalyticsKpiRegistryInterface::class);
        $registry->method('list')->willReturn([
            ['key' => 'gross_minor'],
        ]);

        $vendorContext = new AnalyticsVendorContext();
        $vendorContext->set('acme');

        $service = new AnalyticsHealthService(
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
            $vendorContext,
        );

        $status = $service->status();

        self::assertTrue($status['vendor_context_enabled']);
        self::assertSame('acme', $status['vendor']);
    }
}
