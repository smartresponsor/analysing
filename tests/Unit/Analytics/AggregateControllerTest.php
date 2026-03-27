<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\Controller\Analytics\AggregateController;
use App\ServiceInterface\Analytics\AggregateServiceInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

final class AggregateControllerTest extends TestCase
{
    public function testFunnelReturnsBadRequestWhenAppMissing(): void
    {
        $service = $this->createMock(AggregateServiceInterface::class);
        $controller = new AggregateController($service, $this->createMock(LoggerInterface::class));
        $request = new Request([], [], [], [], [], [], json_encode([
            'env' => 'prod',
            'steps' => ['view', 'checkout'],
            'from' => '2026-01-01 00:00:00',
            'to' => '2026-01-02 00:00:00',
        ], JSON_THROW_ON_ERROR));

        $response = $controller->funnel($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(400, $response->getStatusCode());
        self::assertSame('Invalid aggregate request.', $payload['error']);
        self::assertSame('funnel', $payload['operation']);
    }

    public function testPathReturnsServiceUnavailableWhenServiceFails(): void
    {
        $service = $this->createMock(AggregateServiceInterface::class);
        $service->method('computePath')->willThrowException(new \RuntimeException('broken'));

        $controller = new AggregateController($service, $this->createMock(LoggerInterface::class));
        $request = new Request([], [], [], [], [], [], json_encode([
            'app' => 'shop',
            'env' => 'prod',
            'day' => '2026-01-10 00:00:00',
            'top' => 5,
        ], JSON_THROW_ON_ERROR));

        $response = $controller->path($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(503, $response->getStatusCode());
        self::assertSame('Aggregate data unavailable.', $payload['error']);
        self::assertSame('path', $payload['operation']);
    }
}
