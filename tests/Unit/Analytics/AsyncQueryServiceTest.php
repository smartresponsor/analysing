<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\Service\Analytics\AsyncQueryService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class AsyncQueryServiceTest extends TestCase
{
    public function testSubmitAndStatusReturnNormalizedJob(): void
    {
        $service = new AsyncQueryService(new NullLogger());

        $id = $service->submit([
            'metric' => 'orders',
            'ids' => [1, 2, 3],
        ]);

        self::assertNotSame('', $id);

        $status = $service->status($id);

        self::assertSame($id, $status['id']);
        self::assertSame('done', $status['state']);
        self::assertArrayHasKey('query_checksum', $status);
        self::assertArrayHasKey('age_ms', $status);
        self::assertSame('orders', $status['result']['query']['metric']);
    }

    public function testSubmitRejectsEmptyPayload(): void
    {
        $service = new AsyncQueryService(new NullLogger());

        $this->expectException(\InvalidArgumentException::class);
        $service->submit([]);
    }
}
