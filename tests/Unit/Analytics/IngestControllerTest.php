<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\Controller\Analytics\IngestController;
use App\DomainInterface\Analytics\ClickhouseClientInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

final class IngestControllerTest extends TestCase
{
    public function testIngestRudderReturnsAcceptedCounts(): void
    {
        $client = $this->createMock(ClickhouseClientInterface::class);
        $client->expects(self::once())->method('insertJsonEachRow');

        $controller = new IngestController($client, $this->createMock(LoggerInterface::class));
        $request = new Request([], [], [], [], [], [], json_encode([
            'batch' => [
                ['type' => 'track', 'event' => 'OrderPlaced', 'userId' => 'u1'],
                'skip-me',
            ],
        ], JSON_THROW_ON_ERROR));
        $request->headers->set('X-SR-TENANT', 'tenant-1');

        $response = $controller->ingestRudder($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(1, $payload['accepted']);
        self::assertSame(1, $payload['skipped']);
    }

    public function testIngestSegmentReturnsBadRequestForInvalidJson(): void
    {
        $client = $this->createMock(ClickhouseClientInterface::class);
        $controller = new IngestController($client, $this->createMock(LoggerInterface::class));

        $response = $controller->ingestSegment(new Request([], [], [], [], [], [], '{bad'));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(400, $response->getStatusCode());
        self::assertSame('Invalid analytics ingest request.', $payload['error']);
    }
}
