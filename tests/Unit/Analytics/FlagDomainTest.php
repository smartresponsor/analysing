<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\Domain\Analytics\Flag;
use App\DomainInterface\Analytics\ClickhouseClientInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class FlagDomainTest extends TestCase
{
    public function testEvaluateUsesAllowListBeforeRollout(): void
    {
        $domain = new Flag(new class implements ClickhouseClientInterface {
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
        $client = new class implements ClickhouseClientInterface {
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

        $domain = new Flag($client, new NullLogger(), 'secret');
        $result = $domain->expose([
            'flag_key' => 'beta_ui',
            'user_id' => 'user-1',
            'tenant_id' => 'tenant-1',
            'enabled' => true,
        ]);

        self::assertSame('beta_ui', $client->rows[0]['properties']['flag_key']);
        self::assertTrue($result['enabled']);
    }

    public function testEvaluateRejectsInvalidAllowListShape(): void
    {
        $domain = new Flag(new class implements ClickhouseClientInterface {
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
