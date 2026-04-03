<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\Tests\Support\JsonPayloadAssertionsTrait;

use PHPUnit\Framework\TestCase;

final class GuardConfigConsistencyTest extends TestCase
{
    use JsonPayloadAssertionsTrait;

    public function testNamespaceGuardConfigsRemainSynchronized(): void
    {
        $analytics = file_get_contents(__DIR__.'/../../../config/guard/analytics-namespace-guard.json');
        $default = file_get_contents(__DIR__.'/../../../config/guard/namespace-guard.json');

        self::assertIsString($analytics);
        self::assertIsString($default);
        self::assertJson($analytics);
        self::assertJson($default);
        self::assertEquals(json_decode($analytics, true), json_decode($default, true));
    }
}
