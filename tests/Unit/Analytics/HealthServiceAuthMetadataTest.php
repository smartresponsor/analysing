<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Service\AnalyticsHealthService;
use App\Analysing\ServiceInterface\AnalyticsKpiRegistryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class HealthServiceAuthMetadataTest extends TestCase
{
    public function testStatusIncludesAuthMetadata(): void
    {
        /** @var AnalyticsKpiRegistryInterface&MockObject $registry */
        $registry = $this->createMock(AnalyticsKpiRegistryInterface::class);
        $registry->method('list')->willReturn([
            ['key' => 'gross_minor'],
        ]);

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
        );

        $status = $service->status();

        self::assertTrue($status['auth_required']);
        self::assertTrue($status['auth_public_read']);
    }
}
