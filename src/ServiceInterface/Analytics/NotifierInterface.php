<?php

declare(strict_types=1);

namespace App\ServiceInterface\Analytics;

interface NotifierInterface
{
    /**
     * @param array<string,mixed> $payload
     */
    public function send(string $endpoint, array $payload): bool;
}
