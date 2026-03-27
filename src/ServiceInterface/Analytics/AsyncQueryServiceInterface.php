<?php

declare(strict_types=1);

namespace App\ServiceInterface\Analytics;

interface AsyncQueryServiceInterface
{
    /**
     * @param array<string, scalar|list<scalar|null>|null> $query
     *
     * @return non-empty-string
     */
    public function submit(array $query): string;

    /**
     * @return array{
     *   id: string,
     *   state: string,
     *   submitted_at?: string,
     *   result?: array<string,mixed>
     * }
     */
    public function status(string $id): array;
}
