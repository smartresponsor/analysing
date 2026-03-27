<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\Controller\Analytics\ExperimentController;
use App\DomainInterface\Analytics\ExperimentInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

final class ExperimentControllerTest extends TestCase
{
    public function testAllocateReturnsBadRequestForInvalidJson(): void
    {
        $domain = $this->createMock(ExperimentInterface::class);
        $controller = new ExperimentController($domain, $this->createMock(LoggerInterface::class));

        $response = $controller->allocate(new Request([], [], [], [], [], [], '{bad'));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(400, $response->getStatusCode());
        self::assertSame('Invalid experiment request.', $payload['error']);
    }

    public function testAllocateReturnsServiceUnavailableForRuntimeFailure(): void
    {
        $domain = $this->createMock(ExperimentInterface::class);
        $domain->method('assign')->willThrowException(new \RuntimeException('broken'));

        $controller = new ExperimentController($domain, $this->createMock(LoggerInterface::class));
        $response = $controller->allocate(new Request([], [], [], [], [], [], json_encode(['experiment_id' => 'exp', 'user_id' => 'u1'], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(503, $response->getStatusCode());
        self::assertSame('Experiment allocation unavailable.', $payload['error']);
    }
}
