<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface\Analytics;

interface AggregateServiceInterface
{
    /**
     * @param list<string> $steps
     *
     * @return list<array<string,mixed>>
     */
    public function computeFunnel(string $app, string $env, array $steps, \DateTimeImmutable $from, \DateTimeImmutable $to): array;

    /**
     * @return list<array<string,mixed>>
     */
    public function computeRetention(string $app, string $env, \DateTimeImmutable $cohort, int $days): array;

    /**
     * @return list<array<string,mixed>>
     */
    public function computePath(string $app, string $env, \DateTimeImmutable $day, int $top): array;
}
