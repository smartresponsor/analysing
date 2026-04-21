<?php

/*
 * Marketing America Corp. Oleksandr Tishchenko
 * Author: Oleksandr Tishchenko <dev@highhopesamerica.com>
 */

declare(strict_types=1);

namespace App\Analysing\ControllerInterface\Analytics;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

interface IngestControllerInterface
{
    public function ingestRudder(Request $request): JsonResponse;

    public function ingestSegment(Request $request): JsonResponse;
}
