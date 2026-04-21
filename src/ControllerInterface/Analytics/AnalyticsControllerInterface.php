<?php

/*
 * Marketing America Corp. Oleksandr Tishchenko
 * Author: Oleksandr Tishchenko <dev@highhopesamerica.com>
 */

declare(strict_types=1);

namespace App\Analysing\ControllerInterface\Analytics;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

interface AnalyticsControllerInterface
{
    public function status(): JsonResponse;

    public function funnel(Request $request): JsonResponse;

    public function retention(Request $request): JsonResponse;

    public function cohort(Request $request): JsonResponse;
}
