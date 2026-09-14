<?php

declare(strict_types=1);

namespace App\Analysing\FactoryInterface\Http;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

interface AnalyticsSuccessResponseFactoryInterface
{
    public function create(string $operation, mixed $data, float $startedAt, int $status = Response::HTTP_OK): JsonResponse;
}
