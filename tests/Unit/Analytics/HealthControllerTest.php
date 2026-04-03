<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\Tests\Support\JsonPayloadAssertionsTrait;

use App\Controller\Analytics\HealthController;
use App\ServiceInterface\Analytics\HealthServiceInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class HealthControllerTest extends TestCase
{
    use JsonPayloadAssertionsTrait;

    public function testPingReturnsHealthPayload(): void
    {
        $service = $this->createMock(HealthServiceInterface::class);
        $service->method('status')->willReturn(['ok' => true, 'component' => 'analytics']);

        $controller = new HealthController($service, $this->createMock(LoggerInterface::class));
        $response = $controller->ping();
        $payload = $this->decodeJsonResponse($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($payload['ok']);
    }

    public function testPingReturnsServiceUnavailableOnRuntimeFailure(): void
    {
        $service = $this->createMock(HealthServiceInterface::class);
        $service->method('status')->willThrowException(new \RuntimeException('broken'));

        $controller = new HealthController($service, $this->createMock(LoggerInterface::class));
        $response = $controller->ping();
        $payload = $this->decodeJsonResponse($response);

        self::assertSame(503, $response->getStatusCode());
        self::assertFalse($payload['ok']);
        self::assertSame('Health data unavailable.', $payload['error']);
    }
}
