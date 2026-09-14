<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Service\AnalyticsExperiment;
use App\Analysing\ServiceInterface\AnalyticsClickhouseClientInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class ExperimentTest extends TestCase
{
    public function testAssignReturnsNormalizedAllocation(): void
    {
        $domain = new AnalyticsExperiment(new class implements AnalyticsClickhouseClientInterface {
            public function query(string $sql, array $param = []): array
            {
                return [];
            }

            public function insertJsonEachRow(string $table, array $rows): void
            {
            }
        }, new NullLogger(), 'secret');

        $result = $domain->assign([
            'experiment_id' => 'exp-1',
            'user_id' => 'user-1',
            'rollout' => 100,
        ]);

        self::assertSame('exp-1', $result['experiment_id']);
        self::assertSame('user-1', $result['user_id']);
        self::assertSame('treatment', $result['variant']);
        self::assertSame(100, $result['rollout']);
    }

    public function testExposeWritesExposureEvent(): void
    {
        $client = new class implements AnalyticsClickhouseClientInterface {
            public string $table = '';
            /** @var list<array<string,mixed>> */
            public array $rows = [];

            public function query(string $sql, array $param = []): array
            {
                return [];
            }

            public function insertJsonEachRow(string $table, array $rows): void
            {
                $this->table = $table;
                $this->rows = $rows;
            }
        };

        $domain = new AnalyticsExperiment($client, new NullLogger(), 'secret');
        $result = $domain->expose([
            'user_id' => 'user-1',
            'vendor_id' => 'vendor-1',
            'experiment_id' => 'exp-1',
            'variant' => 'control',
        ]);

        self::assertSame('event_raw', $client->table);
        self::assertIsArray($client->rows[0]['properties']);
        self::assertSame('control', $client->rows[0]['properties']['variant']);
        self::assertSame(1, $result['accepted']);
    }

    public function testAssignRejectsMissingUserId(): void
    {
        $domain = new AnalyticsExperiment(new class implements AnalyticsClickhouseClientInterface {
            public function query(string $sql, array $param = []): array
            {
                return [];
            }

            public function insertJsonEachRow(string $table, array $rows): void
            {
            }
        }, new NullLogger(), 'secret');

        $this->expectException(\InvalidArgumentException::class);
        $domain->assign([
            'experiment_id' => 'exp-1',
            'rollout' => 50,
        ]);
    }
}
