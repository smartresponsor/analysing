<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Entity\Alerts\AlertLog;
use PHPUnit\Framework\TestCase;

final class AlertLogEntityTest extends TestCase
{
    public function testConstructsWithNormalizedContext(): void
    {
        $log = new AlertLog(7, 'delivery', 'sent', ['channel' => 'email', 'meta' => ['attempt' => 1]]);

        self::assertSame(7, $log->getVendorId());
        self::assertSame('delivery', $log->getType());
        self::assertSame('sent', $log->getMessage());
        self::assertSame(['channel' => 'email', 'meta' => ['attempt' => 1]], $log->getContext());
    }

    public function testRejectsInvalidVendorId(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new AlertLog(0, 'delivery', 'sent');
    }
}
