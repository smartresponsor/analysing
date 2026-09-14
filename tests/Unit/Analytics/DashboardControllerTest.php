<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Controller\AnalyticsDashboardController;
use App\Analysing\ServiceInterface\AnalyticsDashboardServiceInterface;
use App\Analysing\Tests\Support\AnalyticsHttpFactoriesTrait;
use App\Analysing\Tests\Support\AnalyticsJsonPayloadAssertionsTrait;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

final class DashboardControllerTest extends TestCase
{
    use AnalyticsHttpFactoriesTrait;
    use AnalyticsJsonPayloadAssertionsTrait;

    public function testKpiReturnsBadRequestForInvalidVendorId(): void
    {
        $request = new Request(['vendorId' => 'abc']);
        $service = $this->createMock(AnalyticsDashboardServiceInterface::class);
        $controller = new AnalyticsDashboardController(
            $service,
            $this->createMock(LoggerInterface::class),
            $this->createSuccessFactory($request),
            $this->createErrorFactory($request),
        );

        $response = $controller->kpi($request);
        $payload = $this->decodeJsonResponse($response);

        self::assertSame(400, $response->getStatusCode());
        self::assertSame('Invalid dashboard request.', $payload['error']);
        self::assertSame('kpi', $payload['operation']);
    }

    public function testTimeseriesReturnsServiceUnavailableWhenServiceFails(): void
    {
        $request = new Request(['currency' => 'usd']);
        $service = $this->createMock(AnalyticsDashboardServiceInterface::class);
        $service->method('timeseries')->willThrowException(new \RuntimeException('db down'));

        $controller = new AnalyticsDashboardController(
            $service,
            $this->createMock(LoggerInterface::class),
            $this->createSuccessFactory($request),
            $this->createErrorFactory($request),
        );
        $response = $controller->timeseries($request);
        $payload = $this->decodeJsonResponse($response);

        self::assertSame(503, $response->getStatusCode());
        self::assertSame('Dashboard data unavailable.', $payload['error']);
        self::assertSame('timeseries', $payload['operation']);
    }
}
