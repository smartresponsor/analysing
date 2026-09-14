<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Controller\AnalyticsAggregateController;
use App\Analysing\ServiceInterface\AnalyticsAggregateServiceInterface;
use App\Analysing\Tests\Support\AnalyticsHttpFactoriesTrait;
use App\Analysing\Tests\Support\AnalyticsJsonPayloadAssertionsTrait;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

final class AggregateControllerTest extends TestCase
{
    use AnalyticsHttpFactoriesTrait;
    use AnalyticsJsonPayloadAssertionsTrait;

    public function testFunnelReturnsBadRequestWhenAppMissing(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([
            'env' => 'prod',
            'steps' => ['view', 'checkout'],
            'from' => '2026-01-01 00:00:00',
            'to' => '2026-01-02 00:00:00',
        ], JSON_THROW_ON_ERROR));
        $service = $this->createMock(AnalyticsAggregateServiceInterface::class);
        $controller = new AnalyticsAggregateController(
            $service,
            $this->createMock(LoggerInterface::class),
            $this->createSuccessFactory($request),
            $this->createErrorFactory($request),
        );

        $response = $controller->funnel($request);
        $payload = $this->decodeJsonResponse($response);

        self::assertSame(400, $response->getStatusCode());
        self::assertSame('Invalid aggregate request.', $payload['error']);
        self::assertSame('funnel', $payload['operation']);
    }

    public function testPathReturnsServiceUnavailableWhenServiceFails(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([
            'app' => 'shop',
            'env' => 'prod',
            'day' => '2026-01-10 00:00:00',
            'top' => 5,
        ], JSON_THROW_ON_ERROR));
        $service = $this->createMock(AnalyticsAggregateServiceInterface::class);
        $service->method('computePath')->willThrowException(new \RuntimeException('broken'));

        $controller = new AnalyticsAggregateController(
            $service,
            $this->createMock(LoggerInterface::class),
            $this->createSuccessFactory($request),
            $this->createErrorFactory($request),
        );

        $response = $controller->path($request);
        $payload = $this->decodeJsonResponse($response);

        self::assertSame(503, $response->getStatusCode());
        self::assertSame('Aggregate data unavailable.', $payload['error']);
        self::assertSame('path', $payload['operation']);
    }
}
