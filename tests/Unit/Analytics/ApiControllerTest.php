<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Controller\Analytics\ApiController;
use App\Analysing\ServiceInterface\Analytics\KpiRegistryInterface;
use App\Analysing\Tests\Support\AnalyticsHttpFactoriesTrait;
use App\Analysing\Tests\Support\JsonPayloadAssertionsTrait;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class ApiControllerTest extends TestCase
{
    use AnalyticsHttpFactoriesTrait;
    use JsonPayloadAssertionsTrait;

    public function testMetricsReturnsCatalogPayload(): void
    {
        $registry = $this->createMock(KpiRegistryInterface::class);
        $registry->method('list')->willReturn(['orders' => 'Orders', 'revenue' => 'Revenue']);

        $controller = new ApiController(
            $registry,
            $this->createMock(LoggerInterface::class),
            $this->createSuccessFactory(),
            $this->createErrorFactory(),
        );
        $response = $controller->metrics();
        $payload = $this->decodeJsonResponse($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($payload['ok']);
        self::assertSame('analytics', $payload['component']);
        self::assertSame('metrics', $payload['operation']);
        $data = $this->requireArrayAt($payload, 'data');
        self::assertSame(2, $data['metric_count']);
        self::assertIsArray($data['metrics']);
        self::assertIsArray($data['metrics'][0]);
        self::assertSame('orders', $data['metrics'][0]['key']);
    }

    public function testMetricsReturnsServiceUnavailableWhenRegistryFails(): void
    {
        $registry = $this->createMock(KpiRegistryInterface::class);
        $registry->method('list')->willThrowException(new \RuntimeException('broken'));

        $controller = new ApiController(
            $registry,
            $this->createMock(LoggerInterface::class),
            $this->createSuccessFactory(),
            $this->createErrorFactory(),
        );
        $response = $controller->metrics();
        $payload = $this->decodeJsonResponse($response);

        self::assertSame(503, $response->getStatusCode());
        self::assertFalse($payload['ok']);
        self::assertSame('Metrics catalog unavailable.', $payload['error']);
    }
}
