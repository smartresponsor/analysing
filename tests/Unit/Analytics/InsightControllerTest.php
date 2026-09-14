<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Controller\AnalyticsInsightController;
use App\Analysing\ServiceInterface\AnalyticsInsightInterface;
use App\Analysing\Tests\Support\AnalyticsHttpFactoriesTrait;
use App\Analysing\Tests\Support\AnalyticsJsonPayloadAssertionsTrait;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

final class InsightControllerTest extends TestCase
{
    use AnalyticsHttpFactoriesTrait;
    use AnalyticsJsonPayloadAssertionsTrait;

    public function testAnomalyReturnsBadRequestForInvalidJson(): void
    {
        $request = new Request([], [], [], [], [], [], '{bad');
        $domain = $this->createMock(AnalyticsInsightInterface::class);
        $controller = new AnalyticsInsightController(
            $domain,
            $this->createMock(LoggerInterface::class),
            $this->createSuccessFactory($request),
            $this->createErrorFactory($request),
        );

        $response = $controller->anomaly($request);
        $payload = $this->decodeJsonResponse($response);

        self::assertSame(400, $response->getStatusCode());
        self::assertSame('Invalid insight request.', $payload['error']);
    }

    public function testMetricTreeReturnsServiceUnavailableForRuntimeFailure(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode(['name' => 'revenue'], JSON_THROW_ON_ERROR));
        $domain = $this->createMock(AnalyticsInsightInterface::class);
        $domain->method('computeMetricTree')->willThrowException(new \RuntimeException('broken'));

        $controller = new AnalyticsInsightController(
            $domain,
            $this->createMock(LoggerInterface::class),
            $this->createSuccessFactory($request),
            $this->createErrorFactory($request),
        );
        $response = $controller->metricTree($request);
        $payload = $this->decodeJsonResponse($response);

        self::assertSame(503, $response->getStatusCode());
        self::assertSame('AnalyticsInsight data unavailable.', $payload['error']);
    }
}
