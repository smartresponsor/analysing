<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface;

interface AnalyticsClickhouseClientInterface
{
    /**
     * @param array<string, bool|float|int|string|null> $param
     *
     * @return list<array<string,mixed>>
     */
    public function query(string $sql, array $param = []): array;

    /**
     * @param list<array<string,mixed>> $rows
     */
    public function insertJsonEachRow(string $table, array $rows): void;
}
