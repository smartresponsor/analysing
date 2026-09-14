<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Service\AnalyticsMetricIngestService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class MetricIngestServiceTest extends TestCase
{
    public function testIngestAddsMetadataToBufferedPayload(): void
    {
        $service = new AnalyticsMetricIngestService(new NullLogger());

        /** @var array<string,mixed> $payload */
        $payload = [
            'metric' => 'orders',
            'dimensions' => ['vendor' => 'acme'],
        ];
        $service->ingest($payload);

        $buffer = $service->dumpBuffer();

        self::assertCount(1, $buffer);
        self::assertSame('orders', $buffer[0]['metric']);
        self::assertArrayHasKey('ingested_at', $buffer[0]);
        self::assertArrayHasKey('payload_checksum', $buffer[0]);
    }

    public function testIngestRejectsEmptyNestedArray(): void
    {
        $service = new AnalyticsMetricIngestService(new NullLogger());

        $this->expectException(\InvalidArgumentException::class);
        $service->ingest([
            'metric' => 'orders',
            'dimensions' => [],
        ]);
    }
}
