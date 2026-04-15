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
        self::assertNotFalse(strpos($token, '.'));

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
        [$payload, $signature] = explode('.', $token, 2);
        $last = substr($signature, -1);
        $replacement = 'A' === $last ? 'B' : 'A';
        $tampered = $payload.'.'.substr($signature, 0, -1).$replacement;

        self::assertNotSame($token, $tampered);
        self::assertSame([], $service->verify($tampered));
    }
}
