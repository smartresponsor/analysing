<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\Domain\Analytics\Insight;
use App\DomainInterface\Analytics\ClickhouseClientInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class InsightDomainTest extends TestCase
{
    public function testDetectAnomalyReturnsInsufficientDataForShortSeries(): void
    {
        $domain = new Insight(new class implements ClickhouseClientInterface {
            public function query(string $sql, array $param = []): array
            {
                return array_fill(0, 6, ['user_count' => 5]);
            }

            public function insertJsonEachRow(string $table, array $rows): void
            {
            }
        }, new NullLogger());

        $result = $domain->detectAnomaly([
            'tenant_id' => 'tenant-1',
            'event_name' => 'purchase',
            'days' => 7,
        ]);

        self::assertFalse($result['anomaly']);
        self::assertSame('insufficient-data', $result['reason']);
    }

    public function testDetectAnomalyRejectsInvalidUserCountRows(): void
    {
        $domain = new Insight(new class implements ClickhouseClientInterface {
            public function query(string $sql, array $param = []): array
            {
                return [['user_count' => 'bad']];
            }

            public function insertJsonEachRow(string $table, array $rows): void
            {
            }
        }, new NullLogger());

        $this->expectException(\RuntimeException::class);
        $domain->detectAnomaly([
            'tenant_id' => 'tenant-1',
            'event_name' => 'purchase',
            'days' => 7,
        ]);
    }

    public function testComputeMetricTreeRejectsUnknownTree(): void
    {
        $domain = new Insight(new class implements ClickhouseClientInterface {
            public function query(string $sql, array $param = []): array
            {
                return [];
            }

            public function insertJsonEachRow(string $table, array $rows): void
            {
            }
        }, new NullLogger());

        $this->expectException(\InvalidArgumentException::class);
        $domain->computeMetricTree([
            'tenant_id' => 'tenant-1',
            'name' => 'unknown-tree',
        ]);
    }
}
