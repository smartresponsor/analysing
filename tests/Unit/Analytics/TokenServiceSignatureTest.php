<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\Service\Analytics\TokenService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class TokenServiceSignatureTest extends TestCase
{
    public function testSignedTokenRoundTrip(): void
    {
        $service = new TokenService(new NullLogger(), 'test-salt');
        $token = $service->issue([
            'routes' => ['analytics_flag_evaluate'],
            'tenant' => 'acme',
        ], 300);

        self::assertNotSame('', $token);
        self::assertNotSame(false, strpos($token, '.'));

        $claims = $service->verify($token);

        self::assertIsArray($claims['scope'] ?? null);
        $scope = $claims['scope'];
        self::assertIsArray($scope);
        self::assertSame('acme', $scope['tenant'] ?? null);
        self::assertSame(['analytics_flag_evaluate'], $scope['routes'] ?? null);
    }

    public function testTamperedSignatureIsRejected(): void
    {
        $service = new TokenService(new NullLogger(), 'test-salt');
        $token = $service->issue(['admin' => true], 300);
        $tampered = substr($token, 0, -1).'0';

        self::assertSame([], $service->verify($tampered));
    }
}
