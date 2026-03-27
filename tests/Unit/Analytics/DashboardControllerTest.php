<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\Controller\Analytics\DashboardController;
use App\ServiceInterface\Analytics\DashboardServiceInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

final class DashboardControllerTest extends TestCase
{
    public function testKpiReturnsBadRequestForInvalidVendorId(): void
    {
        $service = $this->createMock(DashboardServiceInterface::class);
        $controller = new DashboardController($service, $this->createMock(LoggerInterface::class));

        $response = $controller->kpi(new Request(['vendorId' => 'abc']));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(400, $response->getStatusCode());
        self::assertSame('Invalid dashboard request.', $payload['error']);
        self::assertSame('kpi', $payload['operation']);
    }

    public function testTimeseriesReturnsServiceUnavailableWhenServiceFails(): void
    {
        $service = $this->createMock(DashboardServiceInterface::class);
        $service->method('timeseries')->willThrowException(new \RuntimeException('db down'));

        $controller = new DashboardController($service, $this->createMock(LoggerInterface::class));
        $response = $controller->timeseries(new Request(['currency' => 'usd']));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(503, $response->getStatusCode());
        self::assertSame('Dashboard data unavailable.', $payload['error']);
        self::assertSame('timeseries', $payload['operation']);
    }
}
