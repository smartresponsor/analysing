<?php

declare(strict_types=1);

namespace App\Analysing\FactoryInterface\Http;

use Symfony\Component\HttpFoundation\JsonResponse;

interface AnalyticsErrorResponseFactoryInterface
{
    /** @param array<string,mixed> $extra */
    public function create(
        string $operation,
        string $error,
        string $errorCode,
        int $status,
        float $startedAt,
        bool $retryable = false,
        array $extra = [],
    ): JsonResponse;
}
