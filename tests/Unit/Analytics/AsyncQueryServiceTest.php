<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Service\AnalyticsAsyncQueryService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class AsyncQueryServiceTest extends TestCase
{
    public function testSubmitAndStatusReturnNormalizedJob(): void
    {
        $service = new AnalyticsAsyncQueryService(new NullLogger());

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
        self::assertArrayHasKey('result', $status);
        $result = $status['result'];
        self::assertIsArray($result);
        self::assertArrayHasKey('query', $result);
        $query = $result['query'];
        self::assertIsArray($query);
        self::assertSame('orders', $query['metric']);
    }

    public function testSubmitRejectsEmptyPayload(): void
    {
        $service = new AnalyticsAsyncQueryService(new NullLogger());

        $this->expectException(\InvalidArgumentException::class);
        $service->submit([]);
    }
}
