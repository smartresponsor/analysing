<?php

declare(strict_types=1);

namespace App\ServiceInterface\Analytics;

interface CsvImporterInterface extends CsvImportInterface
{
    /**
     * @return list<array<string,string>>
     */
    public function read(string $csvPath, string $delimiter = ','): array;
}
