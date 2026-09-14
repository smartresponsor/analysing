<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Controller\AnalyticsExperimentController;
use App\Analysing\ServiceInterface\AnalyticsExperimentInterface;
use App\Analysing\Tests\Support\AnalyticsHttpFactoriesTrait;
use App\Analysing\Tests\Support\AnalyticsJsonPayloadAssertionsTrait;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

final class ExperimentControllerTest extends TestCase
{
    use AnalyticsHttpFactoriesTrait;
    use AnalyticsJsonPayloadAssertionsTrait;

    public function testAllocateReturnsBadRequestForInvalidJson(): void
    {
        $request = new Request([], [], [], [], [], [], '{bad');
        $domain = $this->createMock(AnalyticsExperimentInterface::class);
        $controller = new AnalyticsExperimentController(
            $domain,
            $this->createMock(LoggerInterface::class),
            $this->createSuccessFactory($request),
            $this->createErrorFactory($request),
        );

        $response = $controller->allocate($request);
        $payload = $this->decodeJsonResponse($response);

        self::assertSame(400, $response->getStatusCode());
        self::assertSame('Invalid experiment request.', $payload['error']);
    }

    public function testAllocateReturnsServiceUnavailableForRuntimeFailure(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode(['experiment_id' => 'exp', 'user_id' => 'u1'], JSON_THROW_ON_ERROR));
        $domain = $this->createMock(AnalyticsExperimentInterface::class);
        $domain->method('assign')->willThrowException(new \RuntimeException('broken'));

        $controller = new AnalyticsExperimentController(
            $domain,
            $this->createMock(LoggerInterface::class),
            $this->createSuccessFactory($request),
            $this->createErrorFactory($request),
        );
        $response = $controller->allocate($request);
        $payload = $this->decodeJsonResponse($response);

        self::assertSame(503, $response->getStatusCode());
        self::assertSame('AnalyticsExperiment allocation unavailable.', $payload['error']);
    }
}
