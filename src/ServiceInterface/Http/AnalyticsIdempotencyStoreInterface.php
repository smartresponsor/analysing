<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface\Http;

use Symfony\Component\HttpFoundation\Response;

interface AnalyticsIdempotencyStoreInterface
{
    public function isValidKey(string $key): bool;

    /**
     * @return array{status: 'new'|'replay'|'conflict'|'pending', record?: array<string,mixed>}
     */
    public function begin(string $route, string $tenant, string $key, string $requestFingerprint): array;

    public function finalize(string $route, string $tenant, string $key, string $requestFingerprint, Response $response): void;

    /** @param array<string,mixed> $record */
    public function buildReplayResponse(array $record): Response;
}
