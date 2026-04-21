<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface\Analytics;

interface AsyncQueryServiceInterface
{
    /**
     * @param array<string, scalar|list<scalar|null>|null> $query
     *
     * @return non-empty-string
     */
    public function submit(array $query): string;

    /** @return array<string,mixed> */
    public function status(string $id): array;
}
