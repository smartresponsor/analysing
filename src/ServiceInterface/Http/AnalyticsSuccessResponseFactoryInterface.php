<?php

declare(strict_types=1);

namespace App\ServiceInterface\Http;

use Symfony\Component\HttpFoundation\JsonResponse;

interface AnalyticsSuccessResponseFactoryInterface
{
    public function create(string $operation, mixed $data, float $startedAt, int $status = JsonResponse::HTTP_OK): JsonResponse;
}
