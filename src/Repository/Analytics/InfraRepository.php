<?php

declare(strict_types=1);
/*
 * Marketing America Corp. Oleksandr Tishchenko
 * Comments in English only. Singular naming.
 */

namespace App\Repository\Analytics;

use App\RepositoryInterface\Analytics\InfraRepositoryInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Psr\Log\LoggerInterface;

final class InfraRepository implements InfraRepositoryInterface
{
    public function __construct(
        private readonly Connection $connection,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function fetchFunnel(string $app, string $env, array $steps, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $app = $this->normalizeKey($app, 'app');
        $env = $this->normalizeKey($env, 'env');
        $steps = $this->normalizeStepList($steps);
        $this->assertOrderedDateRange($from, $to, 'from', 'to');

        $conditions = [
            'app = :app',
            'env = :env',
            'day BETWEEN :from AND :to',
            'step_1 = :step1',
            'step_2 = :step2',
        ];
        $params = [
            'app' => $app,
            'env' => $env,
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
            'step1' => $steps[0],
            'step2' => $steps[1],
        ];

        if (isset($steps[2])) {
            $conditions[] = 'step_3 = :step3';
            $params['step3'] = $steps[2];
        }
        if (isset($steps[3])) {
            $conditions[] = 'step_4 = :step4';
            $params['step4'] = $steps[3];
        }

        $sql = sprintf(
            'SELECT day, user_count FROM aggregate_funnel_daily WHERE %s ORDER BY day ASC',
            implode(' AND ', $conditions),
        );

        try {
            return $this->normalizeFunnelRows($this->connection->fetchAllAssociative($sql, $params));
        } catch (\Throwable $exception) {
            $this->logger->error('Analytics infra repository funnel query failed.', [
                'exception' => $exception,
                'app' => $app,
                'env' => $env,
                'steps' => $steps,
                'from' => $params['from'],
                'to' => $params['to'],
            ]);

            throw new \RuntimeException('Aggregate funnel repository query failed.', 0, $exception);
        }
    }

    public function fetchRetention(string $app, string $env, \DateTimeImmutable $cohort, int $days): array
    {
        $app = $this->normalizeKey($app, 'app');
        $env = $this->normalizeKey($env, 'env');
        $days = $this->normalizePositiveInteger($days, 'days');

        $sql = 'SELECT day_offset, active_user FROM retention_cohort_daily WHERE app = :app AND env = :env AND cohort = :cohort AND day_offset <= :days ORDER BY day_offset';
        $params = [
            'app' => $app,
            'env' => $env,
            'cohort' => $cohort->format('Y-m-d'),
            'days' => $days,
        ];

        try {
            return $this->normalizeRetentionRows($this->connection->fetchAllAssociative($sql, $params, [
                'days' => ParameterType::INTEGER,
            ]));
        } catch (\Throwable $exception) {
            $this->logger->error('Analytics infra repository retention query failed.', [
                'exception' => $exception,
                'app' => $app,
                'env' => $env,
                'cohort' => $params['cohort'],
                'days' => $days,
            ]);

            throw new \RuntimeException('Aggregate retention repository query failed.', 0, $exception);
        }
    }

    public function fetchPath(string $app, string $env, \DateTimeImmutable $day, int $top): array
    {
        $app = $this->normalizeKey($app, 'app');
        $env = $this->normalizeKey($env, 'env');
        $top = $this->normalizePositiveInteger($top, 'top');

        $sql = 'SELECT from_event, to_event, transition_count FROM path_transition_daily WHERE app = :app AND env = :env AND day = :day ORDER BY transition_count DESC LIMIT :top';
        $params = [
            'app' => $app,
            'env' => $env,
            'day' => $day->format('Y-m-d'),
            'top' => $top,
        ];

        try {
            return $this->normalizePathRows($this->connection->fetchAllAssociative($sql, $params, [
                'top' => ParameterType::INTEGER,
            ]));
        } catch (\Throwable $exception) {
            $this->logger->error('Analytics infra repository path query failed.', [
                'exception' => $exception,
                'app' => $app,
                'env' => $env,
                'day' => $params['day'],
                'top' => $top,
            ]);

            throw new \RuntimeException('Aggregate path repository query failed.', 0, $exception);
        }
    }

    public function upsertExperimentMetricDaily(string $experimentKey, string $variantKey, \DateTimeImmutable $day, int $exposure, int $conversion, float $valueSum): void
    {
        $experimentKey = $this->normalizeKey($experimentKey, 'experimentKey');
        $variantKey = $this->normalizeKey($variantKey, 'variantKey');

        if ($exposure < 0) {
            throw new \InvalidArgumentException('exposure must be zero or greater.');
        }

        if ($conversion < 0) {
            throw new \InvalidArgumentException('conversion must be zero or greater.');
        }

        $sql = 'INSERT INTO experiment_metric_daily (day, experiment_key, variant_key, exposure, conversion, value_sum)
                VALUES (:day, :experiment_key, :variant_key, :exposure, :conversion, :value_sum)
                ON CONFLICT (day, experiment_key, variant_key)
                DO UPDATE SET exposure = EXCLUDED.exposure, conversion = EXCLUDED.conversion, value_sum = EXCLUDED.value_sum';
        $params = [
            'day' => $day->format('Y-m-d'),
            'experiment_key' => $experimentKey,
            'variant_key' => $variantKey,
            'exposure' => $exposure,
            'conversion' => $conversion,
            'value_sum' => $valueSum,
        ];

        try {
            $this->connection->executeStatement($sql, $params, [
                'exposure' => ParameterType::INTEGER,
                'conversion' => ParameterType::INTEGER,
            ]);
        } catch (\Throwable $exception) {
            $this->logger->error('Analytics infra repository experiment upsert failed.', [
                'exception' => $exception,
                'day' => $params['day'],
                'experiment_key' => $experimentKey,
                'variant_key' => $variantKey,
                'exposure' => $exposure,
                'conversion' => $conversion,
            ]);

            throw new \RuntimeException('Experiment metric upsert failed.', 0, $exception);
        }
    }

    /**
     * @param list<array<string,mixed>> $rows
     *
     * @return list<array{day:string,user_count:int}>
     */
    private function normalizeFunnelRows(array $rows): array
    {
        return array_map(function (mixed $row, int $index): array {
            if (!is_array($row)) {
                $this->logger->error('Analytics infra repository funnel query returned an invalid row.', [
                    'row_index' => $index,
                    'row_type' => get_debug_type($row),
                ]);
                throw new \RuntimeException(sprintf('Aggregate funnel repository returned an invalid row at index %d.', $index));
            }

            return [
                'day' => $this->readRequiredString($row, 'day', 'funnel', $index),
                'user_count' => $this->readRequiredInt($row, 'user_count', 'funnel', $index),
            ];
        }, $rows, array_keys($rows));
    }

    /**
     * @param list<array<string,mixed>> $rows
     *
     * @return list<array{day_offset:int,active_user:int}>
     */
    private function normalizeRetentionRows(array $rows): array
    {
        return array_map(function (mixed $row, int $index): array {
            if (!is_array($row)) {
                $this->logger->error('Analytics infra repository retention query returned an invalid row.', [
                    'row_index' => $index,
                    'row_type' => get_debug_type($row),
                ]);
                throw new \RuntimeException(sprintf('Aggregate retention repository returned an invalid row at index %d.', $index));
            }

            return [
                'day_offset' => $this->readRequiredInt($row, 'day_offset', 'retention', $index),
                'active_user' => $this->readRequiredInt($row, 'active_user', 'retention', $index),
            ];
        }, $rows, array_keys($rows));
    }

    /**
     * @param list<array<string,mixed>> $rows
     *
     * @return list<array{from_event:string,to_event:string,transition_count:int}>
     */
    private function normalizePathRows(array $rows): array
    {
        return array_map(function (mixed $row, int $index): array {
            if (!is_array($row)) {
                $this->logger->error('Analytics infra repository path query returned an invalid row.', [
                    'row_index' => $index,
                    'row_type' => get_debug_type($row),
                ]);
                throw new \RuntimeException(sprintf('Aggregate path repository returned an invalid row at index %d.', $index));
            }

            return [
                'from_event' => $this->readRequiredString($row, 'from_event', 'path', $index),
                'to_event' => $this->readRequiredString($row, 'to_event', 'path', $index),
                'transition_count' => $this->readRequiredInt($row, 'transition_count', 'path', $index),
            ];
        }, $rows, array_keys($rows));
    }

    /**
     * @param array<string,mixed> $row
     */
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

    /**
     * @param array<string,mixed> $row
     */
    private function readRequiredInt(array $row, string $field, string $query, int $index): int
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

    private function normalizeKey(string $value, string $field): string
    {
        $normalized = trim($value);

        if ('' === $normalized) {
            throw new \InvalidArgumentException(sprintf('%s must be a non-empty string.', $field));
        }

        return $normalized;
    }

    private function normalizePositiveInteger(int $value, string $field): int
    {
        if ($value <= 0) {
            throw new \InvalidArgumentException(sprintf('%s must be a positive integer.', $field));
        }

        return $value;
    }

    /**
     * @param list<string> $steps
     *
     * @return list<string>
     */
    private function normalizeStepList(array $steps): array
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

    private function assertOrderedDateRange(\DateTimeImmutable $from, \DateTimeImmutable $to, string $fromField, string $toField): void
    {
        if ($from > $to) {
            throw new \InvalidArgumentException(sprintf('%s must be earlier than or equal to %s.', $fromField, $toField));
        }
    }
}
