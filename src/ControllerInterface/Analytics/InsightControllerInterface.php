<?php

/*
 * Marketing America Corp. Oleksandr Tishchenko
 * Author: Oleksandr Tishchenko <dev@highhopesamerica.com>
 */

declare(strict_types=1);

namespace App\ControllerInterface\Analytics;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

interface InsightControllerInterface
{
    public function anomaly(Request $request): JsonResponse;

    public function metricTree(Request $request): JsonResponse;
}
