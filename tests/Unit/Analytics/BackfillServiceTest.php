<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\Service\Analytics\BackfillService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class BackfillServiceTest extends TestCase
{
    public function testRunReturnsWindowInMinutes(): void
    {
        $service = new BackfillService(new NullLogger());

        self::assertSame(2, $service->run(100, 220));
    }

    public function testRunRejectsInvertedRange(): void
    {
        $service = new BackfillService(new NullLogger());

        $this->expectException(\InvalidArgumentException::class);
        $service->run(200, 100);
    }
}
