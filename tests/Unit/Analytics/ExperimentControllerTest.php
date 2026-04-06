<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\Tests\Support\AnalyticsHttpFactoriesTrait;
use App\Tests\Support\JsonPayloadAssertionsTrait;
use App\Controller\Analytics\ExperimentController;
use App\DomainInterface\Analytics\ExperimentInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

final class ExperimentControllerTest extends TestCase
{
    use AnalyticsHttpFactoriesTrait;
    use JsonPayloadAssertionsTrait;

    public function testAllocateReturnsBadRequestForInvalidJson(): void
    {
        $request = new Request([], [], [], [], [], [], '{bad');
        $domain = $this->createMock(ExperimentInterface::class);
        $controller = new ExperimentController(
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
        $domain = $this->createMock(ExperimentInterface::class);
        $domain->method('assign')->willThrowException(new \RuntimeException('broken'));

        $controller = new ExperimentController(
            $domain,
            $this->createMock(LoggerInterface::class),
            $this->createSuccessFactory($request),
            $this->createErrorFactory($request),
        );
        $response = $controller->allocate($request);
        $payload = $this->decodeJsonResponse($response);

        self::assertSame(503, $response->getStatusCode());
        self::assertSame('Experiment allocation unavailable.', $payload['error']);
    }
}
