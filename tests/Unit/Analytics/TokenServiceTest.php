<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\Service\Analytics\TokenService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class TokenServiceTest extends TestCase
{
    public function testIssueAndVerifyRoundTripNormalizedScope(): void
    {
        $service = new TokenService(new NullLogger());

        $token = $service->issue([
            'tenant' => 'acme',
            'nested' => ['b' => 2, 'a' => 1],
        ], 60);

        $payload = $service->verify($token);

        self::assertSame('acme', $payload['scope']['tenant']);
        self::assertSame(['a' => 1, 'b' => 2], $payload['scope']['nested']);
        self::assertIsInt($payload['iat']);
        self::assertIsInt($payload['exp']);
    }

    public function testIssueRejectsTooLargeTtl(): void
    {
        $service = new TokenService(new NullLogger());

        $this->expectException(\InvalidArgumentException::class);
        $service->issue(['tenant' => 'acme'], 604801);
    }

    public function testVerifyRejectsInvalidBase64Token(): void
    {
        $service = new TokenService(new NullLogger());

        self::assertSame([], $service->verify('not-a-base64-token'));
    }
}
