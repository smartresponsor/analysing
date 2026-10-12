<?php

declare(strict_types=1);

namespace App\Analysing\Repository;

use Psr\Log\LoggerInterface;

final readonly class AnalyticsRepositoryRowNormalizer
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    /**
     * @param array<mixed> $rows
     *
     * @return list<array<string,mixed>>
     */
    public function normalizeScalarRows(array $rows): array
    {
        $normalized = [];
        foreach (array_values($rows) as $row) {
            if (!is_array($row)) {
                throw new \RuntimeException('Analytics query row must be an array.');
            }

            $normalizedRow = [];
            foreach ($row as $key => $value) {
                $normalizedRow[(string) $key] = $value;
            }
            $normalized[] = $normalizedRow;
        }

        return $normalized;
    }

    /**
     * @param list<array<string,mixed>> $rows
     *
     * @return list<array{day:string,user_count:int}>
     */
    public function normalizeFunnelRows(array $rows): array
    {
        return array_map(fn (array $row, int $index): array => [
            'day' => $this->readRequiredString($row, 'day', 'funnel', $index),
            'user_count' => $this->readRequiredInt($row, 'user_count', 'funnel', $index),
        ], $rows, array_keys($rows));
    }

    /**
     * @param list<array<string,mixed>> $rows
     *
     * @return list<array{cohort_date:string,user_count:int}>
     */
    public function normalizeCohortRows(array $rows): array
    {
        return array_map(fn (array $row, int $index): array => [
            'cohort_date' => $this->readRequiredString($row, 'cohort_date', 'cohort', $index),
            'user_count' => $this->readRequiredInt($row, 'user_count', 'cohort', $index),
        ], $rows, array_keys($rows));
    }

    /**
     * @param list<array<string,mixed>> $rows
     *
     * @return list<array{user_count:int}>
     */
    public function normalizeSeriesRows(array $rows): array
    {
        return array_map(fn (array $row, int $index): array => [
            'user_count' => $this->readRequiredInt($row, 'user_count', 'series', $index),
        ], $rows, array_keys($rows));
    }

    /**
     * @param list<array<string,mixed>> $rows
     *
     * @return list<array{day_offset:int,active_user:int}>
     */
    public function normalizeRetentionRows(array $rows): array
    {
        return array_map(fn (array $row, int $index): array => [
            'day_offset' => $this->readRequiredInt($row, 'day_offset', 'retention', $index),
            'active_user' => $this->readRequiredInt($row, 'active_user', 'retention', $index),
        ], $rows, array_keys($rows));
    }

    /**
     * @param list<array<string,mixed>> $rows
     *
     * @return list<array{from_event:string,to_event:string,transition_count:int}>
     */
    public function normalizePathRows(array $rows): array
    {
        return array_map(fn (array $row, int $index): array => [
            'from_event' => $this->readRequiredString($row, 'from_event', 'path', $index),
            'to_event' => $this->readRequiredString($row, 'to_event', 'path', $index),
            'transition_count' => $this->readRequiredInt($row, 'transition_count', 'path', $index),
        ], $rows, array_keys($rows));
    }

    /** @param array<string,mixed> $row */
    public function readRequiredInt(array $row, string $field, string $query, int $index): int
    {
        if (!array_key_exists($field, $row) || (!is_scalar($row[$field]) && null !== $row[$field])) {
            $this->logger->error('Analytics infra repository row is missing a required integer field.', [
                'query' => $query,
                'row_index' => $index,
                'field' => $field,
            ]);
            throw new \RuntimeException(sprintf('Aggregate %s repository row %d is missing field %s.', $query, $index, $field));
        }

        return (int) $row[$field];
    }

    /** @param array<string,mixed> $row */
    private function readRequiredString(array $row, string $field, string $query, int $index): string
    {
        if (!array_key_exists($field, $row) || !is_scalar($row[$field])) {
            $this->logger->error('Analytics infra repository row is missing a required scalar string field.', [
                'query' => $query,
                'row_index' => $index,
                'field' => $field,
            ]);
            throw new \RuntimeException(sprintf('Aggregate %s repository row %d is missing field %s.', $query, $index, $field));
        }

        $value = trim((string) $row[$field]);
        if ('' === $value) {
            $this->logger->error('Analytics infra repository row contains an empty required string field.', [
                'query' => $query,
                'row_index' => $index,
                'field' => $field,
            ]);
            throw new \RuntimeException(sprintf('Aggregate %s repository row %d contains an empty field %s.', $query, $index, $field));
        }

        return $value;
    }
}
