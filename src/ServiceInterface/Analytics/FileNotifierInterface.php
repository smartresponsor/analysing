<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface\Analytics;

interface FileNotifierInterface extends NotifierInterface
{
    /**
     * @param array<string,mixed> $payload
     */
    public function send(string $endpoint, array $payload): bool;
}
