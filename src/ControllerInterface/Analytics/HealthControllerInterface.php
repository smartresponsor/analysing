<?php

/*
 * Marketing America Corp. Oleksandr Tishchenko
 * Author: Oleksandr Tishchenko <dev@highhopesamerica.com>
 */

declare(strict_types=1);

namespace App\Analysing\ControllerInterface\Analytics;

use Symfony\Component\HttpFoundation\JsonResponse;

interface HealthControllerInterface
{
    public function ping(): JsonResponse;
}
