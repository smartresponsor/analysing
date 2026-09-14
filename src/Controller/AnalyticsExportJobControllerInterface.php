<?php

declare(strict_types=1);

namespace App\Analysing\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;

interface AnalyticsExportJobControllerInterface
{
    public function status(int $id): JsonResponse;

    public function metrics(): JsonResponse;
}
