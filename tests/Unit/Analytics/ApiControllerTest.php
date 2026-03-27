<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\Controller\Analytics\ApiController;
use App\ServiceInterface\Analytics\KpiRegistryInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class ApiControllerTest extends TestCase
{
    public function testMetricsReturnsCatalogPayload(): void
    {
        $registry = $this->createMock(KpiRegistryInterface::class);
        $registry->method('list')->willReturn(['orders' => 'Orders', 'revenue' => 'Revenue']);

        $controller = new ApiController($registry, $this->createMock(LoggerInterface::class));
        $response = $controller->metrics();
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($payload['ok']);
        self::assertSame('analytics', $payload['component']);
        self::assertSame('metrics', $payload['operation']);
        self::assertSame(2, $payload['metric_count']);
        self::assertSame('orders', $payload['metrics'][0]['key']);
    }

    public function testMetricsReturnsServiceUnavailableWhenRegistryFails(): void
    {
        $registry = $this->createMock(KpiRegistryInterface::class);
        $registry->method('list')->willThrowException(new \RuntimeException('broken'));

        $controller = new ApiController($registry, $this->createMock(LoggerInterface::class));
        $response = $controller->metrics();
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(503, $response->getStatusCode());
        self::assertFalse($payload['ok']);
        self::assertSame('Metrics catalog unavailable.', $payload['error']);
    }
}
