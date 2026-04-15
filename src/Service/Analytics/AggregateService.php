<?php

declare(strict_types=1);
/*
 * Marketing America Corp. Oleksandr Tishchenko
 * Comments in English only. Singular naming.
 */

namespace App\Service\Analytics;

use App\RepositoryInterface\Analytics\InfraRepositoryInterface;
use App\ServiceInterface\Analytics\AggregateServiceInterface;
use Psr\Log\LoggerInterface;

final readonly class AggregateService implements AggregateServiceInterface
{
    public function __construct(
        private InfraRepositoryInterface $repo,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param list<string> $steps
     *
     * @return list<array<string,mixed>>
     */
    public function computeFunnel(string $app, string $env, array $steps, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $app = $this->normalizeLabel($app, 'app');
        $env = $this->normalizeLabel($env, 'env');
        $steps = $this->normalizeSteps($steps);
        $this->assertOrderedRange($from, $to);

        try {
            return $this->normalizeFunnelRows($this->repo->fetchFunnel($app, $env, $steps, $from, $to));
        } catch (\Throwable $exception) {
            $this->logger->error('Analytics aggregate funnel query failed.', [
                'exception' => $exception,
                'app' => $app,
                'env' => $env,
                'steps' => $steps,
                'from' => $from->format(DATE_ATOM),
                'to' => $to->format(DATE_ATOM),
            ]);

            throw new \RuntimeException('Aggregate funnel data unavailable.', 0, $exception);
        }
    }

    /** @return list<array<string,mixed>> */
    public function computeRetention(string $app, string $env, \DateTimeImmutable $cohort, int $days): array
    {
        $app = $this->normalizeLabel($app, 'app');
        $env = $this->normalizeLabel($env, 'env');

        if ($days <= 0) {
            throw new \InvalidArgumentException('days must be a positive integer.');
        }

        try {
            return $this->normalizeRetentionRows($this->repo->fetchRetention($app, $env, $cohort, $days));
        } catch (\Throwable $exception) {
            $this->logger->error('Analytics aggregate retention query failed.', [
                'exception' => $exception,
                'app' => $app,
                'env' => $env,
                'cohort' => $cohort->format('Y-m-d'),
                'days' => $days,
            ]);

            throw new \RuntimeException('Aggregate retention data unavailable.', 0, $exception);
        }
    }

    /** @return list<array<string,mixed>> */
    public function computePath(string $app, string $env, \DateTimeImmutable $day, int $top): array
    {
        $app = $this->normalizeLabel($app, 'app');
        $env = $this->normalizeLabel($env, 'env');

        if ($top <= 0) {
            throw new \InvalidArgumentException('top must be a positive integer.');
        }

        try {
            return $this->normalizePathRows($this->repo->fetchPath($app, $env, $day, $top));
        } catch (\Throwable $exception) {
            $this->logger->error('Analytics aggregate path query failed.', [
                'exception' => $exception,
                'app' => $app,
                'env' => $env,
                'day' => $day->format('Y-m-d'),
                'top' => $top,
            ]);

            throw new \RuntimeException('Aggregate path data unavailable.', 0, $exception);
        }
    }

    /**
     * @param list<array<string,mixed>> $rows
     *
     * @return list<array{day:string,user_count:int}>
     */
    private function normalizeFunnelRows(array $rows): array
    {
        return array_map(fn (array $row): array => [
            'day' => $this->readRequiredString($row, 'day'),
            'user_count' => $this->readRequiredInt($row, 'user_count'),
        ], $rows);
    }

    /**
     * @param list<array<string,mixed>> $rows
     *
     * @return list<array{day_offset:int,active_user:int}>
     */
    private function normalizeRetentionRows(array $rows): array
    {
        return array_map(fn (array $row): array => [
            'day_offset' => $this->readRequiredInt($row, 'day_offset'),
            'active_user' => $this->readRequiredInt($row, 'active_user'),
        ], $rows);
    }

    /**
     * @param list<array<string,mixed>> $rows
     *
     * @return list<array{from_event:string,to_event:string,transition_count:int}>
     */
    private function normalizePathRows(array $rows): array
    {
        return array_map(fn (array $row): array => [
            'from_event' => $this->readRequiredString($row, 'from_event'),
            'to_event' => $this->readRequiredString($row, 'to_event'),
            'transition_count' => $this->readRequiredInt($row, 'transition_count'),
        ], $rows);
    }

    /**
     * @param array<string,mixed> $row
     */
    private function readRequiredString(array $row, string $field): string
    {
        if (!array_key_exists($field, $row) || !is_scalar($row[$field])) {
            throw new \RuntimeException(sprintf('Aggregate row is missing required field %s.', $field));
        }

        $value = trim((string) $row[$field]);
        if ('' === $value) {
            throw new \RuntimeException(sprintf('Aggregate row contains an empty field %s.', $field));
        }

        return $value;
    }

    /**
     * @param array<string,mixed> $row
     */
    private function readRequiredInt(array $row, string $field): int
    {
        if (!array_key_exists($field, $row) || (!is_scalar($row[$field]) && null !== $row[$field])) {
            throw new \RuntimeException(sprintf('Aggregate row is missing required field %s.', $field));
        }

        return (int) $row[$field];
    }

    private function normalizeLabel(string $value, string $field): string
    {
        $normalized = trim($value);

        if ('' === $normalized) {
            throw new \InvalidArgumentException(sprintf('%s must be a non-empty string.', $field));
        }

        return $normalized;
    }

    /**
     * @param list<string> $steps
     *
     * @return list<string>
     */
    private function normalizeSteps(array $steps): array
    {
        $normalized = [];

        foreach ($steps as $step) {
            if (!is_scalar($step)) {
                throw new \InvalidArgumentException('steps must contain only scalar values.');
            }

            $value = trim((string) $step);

            if ('' === $value) {
                throw new \InvalidArgumentException('steps must contain only non-empty values.');
            }

            $normalized[] = $value;
        }

        $normalized = array_values(array_unique($normalized));

        if (count($normalized) < 2) {
            throw new \InvalidArgumentException('steps must contain at least two unique values.');
        }

        return array_slice($normalized, 0, 4);
    }

    private function assertOrderedRange(\DateTimeImmutable $from, \DateTimeImmutable $to): void
    {
        if ($from > $to) {
            throw new \InvalidArgumentException('from must be earlier than or equal to to.');
        }
    }
}
