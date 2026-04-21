<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Controller\Analytics\AnalyticsController;
use App\Analysing\DomainInterface\Analytics\AnalyticsInterface;
use App\Analysing\Tests\Support\AnalyticsHttpFactoriesTrait;
use App\Analysing\Tests\Support\JsonPayloadAssertionsTrait;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

final class AnalyticsControllerTest extends TestCase
{
    use AnalyticsHttpFactoriesTrait;
    use JsonPayloadAssertionsTrait;

    public function testStatusReturnsOkPayload(): void
    {
        $domain = $this->createMock(AnalyticsInterface::class);
        $controller = new AnalyticsController(
            $domain,
            $this->createMock(LoggerInterface::class),
            $this->createSuccessFactory(),
            $this->createErrorFactory(),
        );

        $response = $controller->status();
        $payload = $this->decodeJsonResponse($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($payload['ok']);
        self::assertSame('analytics', $payload['component']);
        $data = $this->requireArrayAt($payload, 'data');
        self::assertSame('ok', $data['status']);
        self::assertSame('status', $payload['operation']);
    }

    public function testFunnelReturnsBadRequestForInvalidJson(): void
    {
        $request = new Request([], [], [], [], [], [], '{bad json');
        $domain = $this->createMock(AnalyticsInterface::class);
        $controller = new AnalyticsController(
            $domain,
            $this->createMock(LoggerInterface::class),
            $this->createSuccessFactory($request),
            $this->createErrorFactory($request),
        );

        $response = $controller->funnel($request);
        $payload = $this->decodeJsonResponse($response);

        self::assertSame(400, $response->getStatusCode());
        self::assertSame('Invalid analytics request.', $payload['error']);
        self::assertSame('funnel', $payload['operation']);
    }

    public function testRetentionReturnsServiceUnavailableForRuntimeFailure(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([
            'tenant_id' => 'tenant',
            'app' => 'shop',
            'env' => 'prod',
            'from' => '2026-01-01 00:00:00',
            'to' => '2026-01-31 23:59:59',
            'cohort' => '2026-01-01 00:00:00',
            'days' => 30,
        ], JSON_THROW_ON_ERROR));
        $domain = $this->createMock(AnalyticsInterface::class);
        $domain->method('runRetention')->willThrowException(new \RuntimeException('db down'));

        $controller = new AnalyticsController(
            $domain,
            $this->createMock(LoggerInterface::class),
            $this->createSuccessFactory($request),
            $this->createErrorFactory($request),
        );

        $response = $controller->retention($request);
        $payload = $this->decodeJsonResponse($response);

        self::assertSame(503, $response->getStatusCode());
        self::assertSame('Analytics data unavailable.', $payload['error']);
        self::assertSame('retention', $payload['operation']);
    }
}
