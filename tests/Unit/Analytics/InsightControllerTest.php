<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\Tests\Support\AnalyticsHttpFactoriesTrait;
use App\Tests\Support\JsonPayloadAssertionsTrait;
use App\Controller\Analytics\InsightController;
use App\DomainInterface\Analytics\InsightInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

final class InsightControllerTest extends TestCase
{
    use AnalyticsHttpFactoriesTrait;
    use JsonPayloadAssertionsTrait;

    public function testAnomalyReturnsBadRequestForInvalidJson(): void
    {
        $request = new Request([], [], [], [], [], [], '{bad');
        $domain = $this->createMock(InsightInterface::class);
        $controller = new InsightController(
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
        $domain = $this->createMock(InsightInterface::class);
        $domain->method('computeMetricTree')->willThrowException(new \RuntimeException('broken'));

        $controller = new InsightController(
            $domain,
            $this->createMock(LoggerInterface::class),
            $this->createSuccessFactory($request),
            $this->createErrorFactory($request),
        );
        $response = $controller->metricTree($request);
        $payload = $this->decodeJsonResponse($response);

        self::assertSame(503, $response->getStatusCode());
        self::assertSame('Insight data unavailable.', $payload['error']);
    }
}
