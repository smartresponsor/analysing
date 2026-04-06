<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\Controller\Analytics\DashboardPageController;
use App\Service\Analytics\DashboardHtmlRenderer;
use App\Service\Http\AnalyticsErrorResponseFactory;
use App\Service\Http\AnalyticsSuccessResponseFactory;
use App\Service\Http\RequestCorrelationIdProvider;
use App\Service\Http\TenantContext;
use App\ServiceInterface\Analytics\DashboardServiceInterface;
use App\Tests\Support\JsonPayloadAssertionsTrait;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class DashboardPageControllerTest extends TestCase
{
    use JsonPayloadAssertionsTrait;

    public function testIndexReturnsBadRequestForInvalidCurrencyInJsonMode(): void
    {
        $service = $this->createMock(DashboardServiceInterface::class);
        $controller = $this->createController($service);

        $response = $controller->index(new Request(['currency' => 'toolong', 'format' => 'json']));
        $payload = $this->decodeJsonResponse($response);

        self::assertSame(400, $response->getStatusCode());
        self::assertSame('application/json', $response->headers->get('Content-Type'));
        self::assertSame('Invalid dashboard request.', $payload['error']);
    }

    public function testIndexReturnsServiceUnavailableWhenDashboardFailsInHtmlMode(): void
    {
        $service = $this->createMock(DashboardServiceInterface::class);
        $service->method('kpi')->willThrowException(new \RuntimeException('broken'));

        $controller = $this->createController($service);
        $response = $controller->index(new Request());

        self::assertSame(503, $response->getStatusCode());
        self::assertStringContainsString('text/html', (string) $response->headers->get('Content-Type'));
        self::assertStringContainsString('Dashboard unavailable', (string) $response->getContent());
    }

    public function testIndexReturnsHtmlPageByDefault(): void
    {
        $service = $this->createMock(DashboardServiceInterface::class);
        $service->method('kpi')->willReturn([
            'gross_minor' => 182500,
            'net_minor' => 124100,
            'margin_pct' => 68.0,
            'days' => 7,
        ]);
        $service->method('timeseries')->willReturn([
            ['date' => '2026-04-01', 'gross_minor' => 12300, 'net_minor' => 9800],
        ]);
        $service->method('byVendor')->willReturn([
            ['vendor_id' => 7, 'gross_minor' => 182500, 'net_minor' => 124100, 'margin_pct' => 68.0],
        ]);

        $controller = $this->createController($service);
        $response = $controller->index(new Request(['currency' => 'USD']));

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('text/html', (string) $response->headers->get('Content-Type'));
        self::assertStringContainsString('Analytics dashboard', (string) $response->getContent());
        self::assertStringContainsString('1,825.00', (string) $response->getContent());
        self::assertStringContainsString('Correlation ID', (string) $response->getContent());
    }

    public function testIndexReturnsJsonWhenRequestedByAcceptHeader(): void
    {
        $service = $this->createMock(DashboardServiceInterface::class);
        $service->method('kpi')->willReturn([
            'gross_minor' => 182500,
            'net_minor' => 124100,
            'margin_pct' => 68.0,
            'days' => 7,
        ]);
        $service->method('timeseries')->willReturn([]);
        $service->method('byVendor')->willReturn([]);

        $controller = $this->createController($service);
        $request = new Request(['currency' => 'USD']);
        $request->headers->set('Accept', 'application/json');
        $response = $controller->index($request);
        $payload = $this->decodeJsonResponse($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('dashboard_page', $payload['operation']);
        self::assertSame('analytics', $payload['component']);
    }

    private function createController(DashboardServiceInterface $service): DashboardPageController
    {
        $requestStack = new RequestStack();
        $tenantContext = new TenantContext();
        $renderer = new DashboardHtmlRenderer(new RequestCorrelationIdProvider($requestStack), $tenantContext);
        $successResponses = new AnalyticsSuccessResponseFactory(new RequestCorrelationIdProvider($requestStack), $tenantContext);
        $errorResponses = new AnalyticsErrorResponseFactory(new RequestCorrelationIdProvider($requestStack), $tenantContext);

        return new DashboardPageController(
            $service,
            $this->createMock(LoggerInterface::class),
            $renderer,
            $successResponses,
            $errorResponses,
        );
    }
}
