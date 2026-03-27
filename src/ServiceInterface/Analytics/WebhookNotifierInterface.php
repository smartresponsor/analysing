<?php

declare(strict_types=1);

namespace App\ServiceInterface\Analytics;

interface WebhookNotifierInterface extends NotifierInterface
{
    /**
     * @param array<string,mixed> $payload
     */
    public function send(string $endpoint, array $payload): bool;
}
