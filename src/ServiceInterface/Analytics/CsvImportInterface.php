<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface\Analytics;

interface CsvImportInterface
{
    /**
     * @return list<array<string,string>>
     */
    public function read(string $csvPath, string $delimiter = ','): array;
}
