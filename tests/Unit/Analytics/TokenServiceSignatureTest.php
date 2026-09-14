<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Service\AnalyticsTokenService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class TokenServiceSignatureTest extends TestCase
{
    public function testSignedTokenRoundTrip(): void
    {
        $service = new AnalyticsTokenService(new NullLogger(), 'test-salt');
        $token = $service->issue([
            'routes' => ['analytics_flag_evaluate'],
            'vendor' => 'acme',
        ], 300);

        self::assertNotSame('', $token);
        self::assertNotFalse(strpos($token, '.'));

        $claims = $service->verify($token);

        self::assertArrayHasKey('scope', $claims);
        $scope = $claims['scope'];
        self::assertIsArray($scope);
        self::assertSame('acme', $scope['vendor'] ?? null);
        self::assertSame(['analytics_flag_evaluate'], $scope['routes'] ?? null);
    }

    public function testTamperedSignatureIsRejected(): void
    {
        $service = new AnalyticsTokenService(new NullLogger(), 'test-salt');
        $token = $service->issue(['admin' => true], 300);
        [$payload, $signature] = explode('.', $token, 2);
        $last = substr($signature, -1);
        $replacement = 'A' === $last ? 'B' : 'A';
        $tampered = $payload.'.'.substr($signature, 0, -1).$replacement;

        self::assertNotSame($token, $tampered);
        self::assertSame([], $service->verify($tampered));
    }
}
