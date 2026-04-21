<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Service\Analytics\TokenService;
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
        self::assertArrayHasKey('scope', $payload);
        self::assertArrayHasKey('iat', $payload);
        self::assertArrayHasKey('exp', $payload);
        $scope = $payload['scope'];
        $iat = $payload['iat'];
        $exp = $payload['exp'];
        self::assertIsArray($scope);
        self::assertIsInt($iat);
        self::assertIsInt($exp);

        self::assertSame('acme', $scope['tenant']);
        self::assertArrayHasKey('nested', $scope);
        self::assertIsArray($scope['nested']);
        self::assertSame(['a' => 1, 'b' => 2], $scope['nested']);
        self::assertIsInt($iat);
        self::assertIsInt($exp);
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
