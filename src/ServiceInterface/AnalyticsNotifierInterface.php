<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface;

interface AnalyticsNotifierInterface
{
    /**
     * @param array<string,mixed> $payload
     */
    public function send(string $endpoint, array $payload): bool;
}
