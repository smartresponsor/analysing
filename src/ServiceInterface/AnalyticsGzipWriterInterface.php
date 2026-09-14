<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface;

interface AnalyticsGzipWriterInterface
{
    /**
     * @param list<array<string,mixed>> $rows
     */
    public function write(string $path, array $rows): string;
}
