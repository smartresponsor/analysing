<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\Controller\Analytics\IngestController;
use App\DomainInterface\Analytics\ClickhouseClientInterface;
use App\Tests\Support\AnalyticsHttpFactoriesTrait;
use App\Tests\Support\JsonPayloadAssertionsTrait;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

final class IngestControllerTest extends TestCase
{
    use AnalyticsHttpFactoriesTrait;
    use JsonPayloadAssertionsTrait;

    public function testIngestRudderReturnsAcceptedCounts(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([
            'batch' => [
                ['type' => 'track', 'event' => 'OrderPlaced', 'userId' => 'u1'],
                'skip-me',
            ],
        ], JSON_THROW_ON_ERROR));
        $request->headers->set('X-SR-TENANT', 'tenant-1');
        $client = $this->createMock(ClickhouseClientInterface::class);
        $client->expects(self::once())->method('insertJsonEachRow');

        $controller = new IngestController(
            $client,
            $this->createMock(LoggerInterface::class),
            $this->createSuccessFactory($request),
            $this->createErrorFactory($request),
        );

        $response = $controller->ingestRudder($request);
        $payload = $this->decodeJsonResponse($response);

        self::assertSame(200, $response->getStatusCode());
        $data = $this->requireArrayAt($payload, 'data');
        self::assertSame(1, $data['accepted']);
        self::assertSame(1, $data['skipped']);
        self::assertSame('rudder', $data['source']);
    }

    public function testIngestSegmentReturnsBadRequestForInvalidJson(): void
    {
        $request = new Request([], [], [], [], [], [], '{bad');
        $client = $this->createMock(ClickhouseClientInterface::class);
        $controller = new IngestController(
            $client,
            $this->createMock(LoggerInterface::class),
            $this->createSuccessFactory($request),
            $this->createErrorFactory($request),
        );

        $response = $controller->ingestSegment($request);
        $payload = $this->decodeJsonResponse($response);

        self::assertSame(400, $response->getStatusCode());
        self::assertSame('Invalid analytics ingest request.', $payload['error']);
    }
}
