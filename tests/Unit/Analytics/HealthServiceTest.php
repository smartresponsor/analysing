<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\Service\Analytics\HealthService;
use App\ServiceInterface\Analytics\KpiRegistryInterface;
use App\ValueObject\Analytics\KpiId;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class HealthServiceTest extends TestCase
{
    public function testStatusSeparatesPlatformHealthFromCatalogReadiness(): void
    {
        $registry = new class implements KpiRegistryInterface {
            public function list(): array { return []; }
            public function has(KpiId $id): bool { return false; }
        };

        $service = new HealthService(new NullLogger(), $registry);
        $status = $service->status();

        self::assertFalse($status['catalog_ready']);
        self::assertSame(0, $status['kpi_catalog_count']);
        self::assertSame([] === $status['missing_required_extensions'], $status['ok']);
    }
}
