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
        $client->query('SELECT * FROM t WHERE id = {id}', ['id' => ['broken']]);
    }

    public function testInsertRejectsEmptyRow(): void
    {
        $client = new ClickhouseClient('http://clickhouse.test', 'user', 'pass');

        $this->expectException(\InvalidArgumentException::class);
        $client->insertJsonEachRow('events', [[]]);
    }
}
