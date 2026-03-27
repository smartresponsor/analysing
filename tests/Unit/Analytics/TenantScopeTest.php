<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\Service\Analytics\TenantScope;
use App\ValueObject\Analytics\TenantId;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class TenantScopeTest extends TestCase
{
    public function testFilterReturnsOnlyRowsForTenant(): void
    {
        $service = new TenantScope(new NullLogger());
        $rows = $service->filter([
            ['tenant' => 'acme', 'metric' => 'sales'],
            ['tenant' => 'other', 'metric' => 'sales'],
            ['metric' => 'missing'],
        ], new TenantId('acme'));

        self::assertCount(1, $rows);
        self::assertSame('acme', $rows[0]['tenant']);
    }
}
