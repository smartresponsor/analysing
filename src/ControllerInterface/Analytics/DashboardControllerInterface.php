<?php

/*
 * Marketing America Corp. Oleksandr Tishchenko
 * Author: Oleksandr Tishchenko <dev@highhopesamerica.com>
 */

declare(strict_types=1);

namespace App\ControllerInterface\Analytics;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

interface DashboardControllerInterface
{
    public function kpi(Request $req): JsonResponse;

    public function timeseries(Request $req): JsonResponse;

    public function topVendors(Request $req): JsonResponse;
}
