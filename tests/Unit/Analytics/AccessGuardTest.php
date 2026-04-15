<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\Service\Analytics\AccessGuard;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class AccessGuardTest extends TestCase
{
    public function testAllowListIsNormalizedAndApplied(): void
    {
        /** @var list<bool|float|int|string> $allow */
        $allow = ['  alpha  ', '', 'beta', 0, false];
        $guard = new AccessGuard(new NullLogger(), $allow);

        self::assertTrue($guard->allow('alpha'));
        self::assertTrue($guard->allow('beta'));
        self::assertFalse($guard->allow('gamma'));
        self::assertFalse($guard->allow('   '));
    }

    public function testOverlongSubjectIsRejected(): void
    {
        $guard = new AccessGuard(new NullLogger(), []);

        $this->expectException(\InvalidArgumentException::class);
        $guard->allow(str_repeat('x', 256));
    }
}
