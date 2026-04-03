<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\Domain\Analytics\ClickhouseClient;
use PHPUnit\Framework\TestCase;

final class ClickhouseClientTest extends TestCase
{
    public function testQueryRejectsNonScalarParameterValues(): void
    {
        $client = new ClickhouseClient('http://clickhouse.test', 'user', 'pass');

        $this->expectException(\InvalidArgumentException::class);
        $reflection = new \ReflectionMethod($client, 'bind');
        $reflection->setAccessible(true);
        $invalid = ['id' => 'ok'];
        $invalid['id'] = ['broken'];
        $reflection->invoke($client, 'SELECT * FROM t WHERE id = {id}', $invalid);
    }

    public function testInsertRejectsEmptyRow(): void
    {
        $client = new ClickhouseClient('http://clickhouse.test', 'user', 'pass');

        $this->expectException(\InvalidArgumentException::class);
        $client->insertJsonEachRow('events', [[]]);
    }
}
