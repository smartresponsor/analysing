<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\Controller\Analytics\AnalyticsController;
use App\DomainInterface\Analytics\AnalyticsInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

final class AnalyticsControllerTest extends TestCase
{
    public function testStatusReturnsOkPayload(): void
    {
        $domain = $this->createMock(AnalyticsInterface::class);
        $controller = new AnalyticsController($domain, $this->createMock(LoggerInterface::class));

        $response = $controller->status();
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($payload['ok']);
        self::assertSame('analytics', $payload['component']);
        self::assertSame('ok', $payload['status']);
    }

    public function testFunnelReturnsBadRequestForInvalidJson(): void
    {
        $domain = $this->createMock(AnalyticsInterface::class);
        $controller = new AnalyticsController($domain, $this->createMock(LoggerInterface::class));
        $request = new Request([], [], [], [], [], [], '{bad json');

        $response = $controller->funnel($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(400, $response->getStatusCode());
        self::assertSame('Invalid analytics request.', $payload['error']);
        self::assertSame('funnel', $payload['operation']);
    }

    public function testRetentionReturnsServiceUnavailableForRuntimeFailure(): void
    {
        $domain = $this->createMock(AnalyticsInterface::class);
        $domain->method('runRetention')->willThrowException(new \RuntimeException('db down'));

        $controller = new AnalyticsController($domain, $this->createMock(LoggerInterface::class));
        $request = new Request([], [], [], [], [], [], json_encode([
            'tenant_id' => 'tenant',
            'app' => 'shop',
            'env' => 'prod',
            'from' => '2026-01-01 00:00:00',
            'to' => '2026-01-31 23:59:59',
            'cohort' => '2026-01-01 00:00:00',
            'days' => 30,
        ], JSON_THROW_ON_ERROR));

        $response = $controller->retention($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(503, $response->getStatusCode());
        self::assertSame('Analytics data unavailable.', $payload['error']);
        self::assertSame('retention', $payload['operation']);
    }
}
