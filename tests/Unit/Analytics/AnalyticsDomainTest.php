<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\Domain\Analytics\Analytics;
use App\DomainInterface\Analytics\ClickhouseClientInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class AnalyticsDomainTest extends TestCase
{
    public function testRunFunnelBuildsScalarStepBindings(): void
    {
        $client = new class implements ClickhouseClientInterface {
            public array $queries = [];

            public function query(string $sql, array $param = []): array
            {
                $this->queries[] = ['sql' => $sql, 'param' => $param];

                return [['day' => '2026-01-01', 'user_count' => 5]];
            }

            public function insertJsonEachRow(string $table, array $rows): void
            {
            }
        };

        $domain = new Analytics($client, new NullLogger());
        $rows = $domain->runFunnel([
            'tenant_id' => 'tenant-1',
            'app' => 'shop',
            'env' => 'prod',
            'from' => '2026-01-01 00:00:00',
            'to' => '2026-01-31 23:59:59',
            'steps' => ['view', 'cart', 'checkout', 'checkout'],
        ]);

        self::assertCount(1, $rows);
        self::assertSame('view', $client->queries[0]['param']['step_1']);
        self::assertSame('cart', $client->queries[0]['param']['step_2']);
        self::assertSame('checkout', $client->queries[0]['param']['step_3']);
        self::assertSame('', $client->queries[0]['param']['step_4']);
        self::assertSame(3, $client->queries[0]['param']['step_count']);
    }

    public function testRunRetentionRejectsInvalidDateOrder(): void
    {
        $domain = new Analytics(new class implements ClickhouseClientInterface {
            public function query(string $sql, array $param = []): array
            {
                return [];
            }

            public function insertJsonEachRow(string $table, array $rows): void
            {
            }
        }, new NullLogger());

        $this->expectException(\InvalidArgumentException::class);
        $domain->runRetention([
            'tenant_id' => 'tenant-1',
            'app' => 'shop',
            'env' => 'prod',
            'from' => '2026-02-01 00:00:00',
            'to' => '2026-01-01 00:00:00',
            'cohort' => '2026-01-01 00:00:00',
            'days' => 30,
        ]);
    }
}
