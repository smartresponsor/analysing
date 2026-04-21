<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Controller\Analytics\HealthController;
use App\Analysing\ServiceInterface\Analytics\HealthServiceInterface;
use App\Analysing\Tests\Support\AnalyticsHttpFactoriesTrait;
use App\Analysing\Tests\Support\JsonPayloadAssertionsTrait;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class HealthControllerTest extends TestCase
{
    use AnalyticsHttpFactoriesTrait;
    use JsonPayloadAssertionsTrait;

    public function testPingReturnsHealthPayload(): void
    {
        $service = $this->createMock(HealthServiceInterface::class);
        $service->method('status')->willReturn(['ok' => true, 'component' => 'analytics']);

        $controller = new HealthController(
            $service,
            $this->createMock(LoggerInterface::class),
            $this->createSuccessFactory(),
            $this->createErrorFactory(),
        );
        $response = $controller->ping();
        $payload = $this->decodeJsonResponse($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($payload['ok']);
        $data = $this->requireArrayAt($payload, 'data');
        self::assertTrue((bool) $data['ok']);
    }

    public function testPingReturnsServiceUnavailableOnRuntimeFailure(): void
    {
        $service = $this->createMock(HealthServiceInterface::class);
        $service->method('status')->willThrowException(new \RuntimeException('broken'));

        $controller = new HealthController(
            $service,
            $this->createMock(LoggerInterface::class),
            $this->createSuccessFactory(),
            $this->createErrorFactory(),
        );
        $response = $controller->ping();
        $payload = $this->decodeJsonResponse($response);

        self::assertSame(503, $response->getStatusCode());
        self::assertFalse($payload['ok']);
        self::assertSame('Health data unavailable.', $payload['error']);
    }
}
