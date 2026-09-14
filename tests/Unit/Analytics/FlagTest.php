<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Service\AnalyticsFlag;
use App\Analysing\ServiceInterface\AnalyticsClickhouseClientInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class FlagTest extends TestCase
{
    public function testEvaluateUsesAllowListBeforeRollout(): void
    {
        $domain = new AnalyticsFlag(new class implements AnalyticsClickhouseClientInterface {
            public function query(string $sql, array $param = []): array
            {
                return [];
            }

            public function insertJsonEachRow(string $table, array $rows): void
            {
            }
        }, new NullLogger(), 'secret');

        $result = $domain->evaluate([
            'flag_key' => 'beta_ui',
            'user_id' => 'user-1',
            'allow' => ['user-1'],
            'rollout' => 0,
        ]);

        self::assertTrue($result['enabled']);
        self::assertSame('allow', $result['reason']);
    }

    public function testExposeWritesFlagExposureEvent(): void
    {
        $client = new class implements AnalyticsClickhouseClientInterface {
            /** @var list<array<string,mixed>> */
            public array $rows = [];

            public function query(string $sql, array $param = []): array
            {
                return [];
            }

            public function insertJsonEachRow(string $table, array $rows): void
            {
                $this->rows = $rows;
            }
        };

        $domain = new AnalyticsFlag($client, new NullLogger(), 'secret');
        $result = $domain->expose([
            'flag_key' => 'beta_ui',
            'user_id' => 'user-1',
            'vendor_id' => 'vendor-1',
            'enabled' => true,
        ]);

        self::assertIsArray($client->rows[0]['properties']);
        self::assertSame('beta_ui', $client->rows[0]['properties']['flag_key']);
        self::assertTrue($result['enabled']);
    }

    public function testEvaluateRejectsInvalidAllowListShape(): void
    {
        $domain = new AnalyticsFlag(new class implements AnalyticsClickhouseClientInterface {
            public function query(string $sql, array $param = []): array
            {
                return [];
            }

            public function insertJsonEachRow(string $table, array $rows): void
            {
            }
        }, new NullLogger(), 'secret');

        $this->expectException(\InvalidArgumentException::class);
        $domain->evaluate([
            'flag_key' => 'beta_ui',
            'user_id' => 'user-1',
            'allow' => 'everyone',
            'rollout' => 10,
        ]);
    }
}
