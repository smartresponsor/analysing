<?php

/*
 * Marketing America Corp. Oleksandr Tishchenko
 * Author: Oleksandr Tishchenko <dev@highhopesamerica.com>
 */

declare(strict_types=1);

namespace App\Analysing\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

interface AnalyticsDashboardControllerInterface
{
    public function kpi(Request $req): JsonResponse;

    public function timeseries(Request $req): JsonResponse;

    public function topVendors(Request $req): JsonResponse;
}
