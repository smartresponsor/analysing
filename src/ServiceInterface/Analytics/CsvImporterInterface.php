<?php

declare(strict_types=1);

namespace App\ServiceInterface\Analytics;

interface CsvImporterInterface
{
    /**
     * @return list<array<string,string>>
     */
    public function read(string $csvPath, string $delimiter = ','): array;
}
