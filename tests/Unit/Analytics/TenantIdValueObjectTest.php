<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\ValueObject\Analytics\TenantId;
use PHPUnit\Framework\TestCase;

final class TenantIdValueObjectTest extends TestCase
{
    public function testTrimsAndStringifiesTenantId(): void
    {
        $tenantId = new TenantId(' demo-tenant ');

        self::assertSame('demo-tenant', $tenantId->value());
        self::assertSame('demo-tenant', (string) $tenantId);
    }

    public function testRejectsEmptyTenantId(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new TenantId('   ');
    }
}
