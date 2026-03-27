<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\Service\Analytics\WindowQuery;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class WindowQueryTest extends TestCase
{
    public function testWindowSplitsRowsIntoFixedSizeChunks(): void
    {
        $service = new WindowQuery(new NullLogger());

        self::assertSame([
            [1, 2],
            [3, 4],
            [5],
        ], $service->window([1, 2, 3, 4, 5], 2));
    }

    public function testWindowRejectsNonPositiveSize(): void
    {
        $service = new WindowQuery(new NullLogger());

        $this->expectException(\InvalidArgumentException::class);
        $service->window([1, 2, 3], 0);
    }
}
