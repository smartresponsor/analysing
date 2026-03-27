<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\Controller\Analytics\DashboardPageController;
use App\ServiceInterface\Analytics\DashboardServiceInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

final class DashboardPageControllerTest extends TestCase
{
    public function testIndexReturnsBadRequestForInvalidCurrency(): void
    {
        $service = $this->createMock(DashboardServiceInterface::class);
        $controller = new DashboardPageController($service, $this->createMock(LoggerInterface::class));

        $response = $controller->index(new Request(['currency' => 'toolong']));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(400, $response->getStatusCode());
        self::assertSame('Invalid dashboard request.', $payload['error']);
    }

    public function testIndexReturnsServiceUnavailableWhenDashboardFails(): void
    {
        $service = $this->createMock(DashboardServiceInterface::class);
        $service->method('kpi')->willThrowException(new \RuntimeException('broken'));

        $controller = new DashboardPageController($service, $this->createMock(LoggerInterface::class));
        $response = $controller->index(new Request());
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(503, $response->getStatusCode());
        self::assertSame('Dashboard data unavailable.', $payload['error']);
    }
}
