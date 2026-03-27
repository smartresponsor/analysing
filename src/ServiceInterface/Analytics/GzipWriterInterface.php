<?php

declare(strict_types=1);

namespace App\ServiceInterface\Analytics;

interface GzipWriterInterface
{
    /**
     * @param list<array<string,mixed>> $rows
     */
    public function write(string $path, array $rows): string;
}
