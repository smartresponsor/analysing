<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Controller\AnalyticsDashboardPageController;
use App\Analysing\Factory\Http\AnalyticsErrorResponseFactory;
use App\Analysing\Factory\Http\AnalyticsSuccessResponseFactory;
use App\Analysing\Provider\Http\AnalyticsRequestCorrelationIdProvider;
use App\Analysing\Service\AnalyticsDashboardHtmlRenderer;
use App\Analysing\Service\Http\AnalyticsVendorContext;
use App\Analysing\ServiceInterface\AnalyticsDashboardServiceInterface;
use App\Analysing\Tests\Support\AnalyticsJsonPayloadAssertionsTrait;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class DashboardPageControllerTest extends TestCase
{
    use AnalyticsJsonPayloadAssertionsTrait;

    public function testIndexReturnsBadRequestForInvalidCurrencyInJsonMode(): void
    {
        $service = $this->createMock(AnalyticsDashboardServiceInterface::class);
        $controller = $this->createController($service);

        $response = $controller->index(new Request(['currency' => 'toolong', 'format' => 'json']));
        $payload = $this->decodeJsonResponse($response);

        self::assertSame(400, $response->getStatusCode());
        self::assertSame('application/json', $response->headers->get('Content-Type'));
        self::assertSame('Invalid dashboard request.', $payload['error']);
    }

    public function testIndexReturnsServiceUnavailableWhenDashboardFailsInHtmlMode(): void
    {
        $service = $this->createMock(AnalyticsDashboardServiceInterface::class);
        $service->method('kpi')->willThrowException(new \RuntimeException('broken'));

        $controller = $this->createController($service);
        $response = $controller->index(new Request());

        self::assertSame(503, $response->getStatusCode());
        self::assertStringContainsString('text/html', (string) $response->headers->get('Content-Type'));
        self::assertStringContainsString('Dashboard unavailable', (string) $response->getContent());
    }

    public function testIndexReturnsHtmlPageByDefault(): void
    {
        $service = $this->createMock(AnalyticsDashboardServiceInterface::class);
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
        $service = $this->createMock(AnalyticsDashboardServiceInterface::class);
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

    private function createController(AnalyticsDashboardServiceInterface $service): AnalyticsDashboardPageController
    {
        $requestStack = new RequestStack();
        $vendorContext = new AnalyticsVendorContext();
        $renderer = new AnalyticsDashboardHtmlRenderer(new AnalyticsRequestCorrelationIdProvider($requestStack), $vendorContext);
        $successResponses = new AnalyticsSuccessResponseFactory(new AnalyticsRequestCorrelationIdProvider($requestStack), $vendorContext);
        $errorResponses = new AnalyticsErrorResponseFactory(new AnalyticsRequestCorrelationIdProvider($requestStack), $vendorContext);

        return new AnalyticsDashboardPageController(
            $service,
            $this->createMock(LoggerInterface::class),
            $renderer,
            $successResponses,
            $errorResponses,
        );
    }
}
