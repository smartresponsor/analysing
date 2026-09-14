<?php

/*
 * Marketing America Corp. Oleksandr Tishchenko
 * Author: Oleksandr Tishchenko <dev@highhopesamerica.com>
 */

declare(strict_types=1);

namespace App\Analysing\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;

interface AnalyticsApiControllerInterface
{
    public function metrics(): JsonResponse;
}
