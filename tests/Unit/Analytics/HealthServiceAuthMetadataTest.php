<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\Service\Analytics\HealthService;
use App\ServiceInterface\Analytics\KpiRegistryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class HealthServiceAuthMetadataTest extends TestCase
{
    public function testStatusIncludesAuthMetadata(): void
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
        );

        $status = $service->status();

        self::assertTrue($status['auth_required']);
        self::assertTrue($status['auth_public_read']);
    }
}
