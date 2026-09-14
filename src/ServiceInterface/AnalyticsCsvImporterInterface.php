<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface;

interface AnalyticsCsvImporterInterface extends AnalyticsCsvImportInterface
{
    /**
     * @return list<array<string,string>>
     */
    public function read(string $csvPath, string $delimiter = ','): array;
}
