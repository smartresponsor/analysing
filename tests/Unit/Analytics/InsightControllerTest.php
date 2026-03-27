<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\Controller\Analytics\InsightController;
use App\DomainInterface\Analytics\InsightInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

final class InsightControllerTest extends TestCase
{
    public function testAnomalyReturnsBadRequestForInvalidJson(): void
    {
        $domain = $this->createMock(InsightInterface::class);
        $controller = new InsightController($domain, $this->createMock(LoggerInterface::class));

        $response = $controller->anomaly(new Request([], [], [], [], [], [], '{bad'));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(400, $response->getStatusCode());
        self::assertSame('Invalid insight request.', $payload['error']);
    }

    public function testMetricTreeReturnsServiceUnavailableForRuntimeFailure(): void
    {
        $domain = $this->createMock(InsightInterface::class);
        $domain->method('computeMetricTree')->willThrowException(new \RuntimeException('broken'));

        $controller = new InsightController($domain, $this->createMock(LoggerInterface::class));
        $response = $controller->metricTree(new Request([], [], [], [], [], [], json_encode(['name' => 'revenue'], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(503, $response->getStatusCode());
        self::assertSame('Insight data unavailable.', $payload['error']);
    }
}
