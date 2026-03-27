<?php

declare(strict_types=1);
/*
 * Marketing America Corp. Oleksandr Tishchenko
 * Comments in English only. Singular naming.
 */

namespace App\ServiceInterface\Analytics;

interface AggregateServiceInterface
{
    public function computeFunnel(string $app, string $env, array $steps, \DateTimeImmutable $from, \DateTimeImmutable $to): array;

    public function computeRetention(string $app, string $env, \DateTimeImmutable $cohort, int $days): array;

    public function computePath(string $app, string $env, \DateTimeImmutable $day, int $top): array;
}
