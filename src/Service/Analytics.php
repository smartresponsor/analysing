<?php

/*
 * Marketing America Corp. Oleksandr Tishchenko
 * Author: Oleksandr Tishchenko <dev@highhopesamerica.com>
 */

declare(strict_types=1);

namespace App\Analysing\Service;

use App\Analysing\RepositoryInterface\AnalyticsRepositoryInterface;
use App\Analysing\ServiceInterface\AnalyticsInterface;
use Psr\Log\LoggerInterface;

final readonly class Analytics implements AnalyticsInterface
{
    public function __construct(
        private AnalyticsRepositoryInterface $repository,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param array<string,mixed> $param
     *
     * @return list<array<string,mixed>>
     */
    public function runFunnel(array $param): array
    {
        $normalized = $this->normalizeRangeParams($param, true);
        $steps = $normalized['steps'] ?? throw new \LogicException('Normalized funnel steps are missing.');
        $rows = $this->repository->fetchFunnel(
            $normalized['app'],
            $normalized['env'],
            $steps,
            new \DateTimeImmutable($normalized['from']),
            new \DateTimeImmutable($normalized['to']),
        );
        $validated = $this->validateQueryRows($rows, ['day', 'user_count'], 'funnel');

        $this->logger->info('Analytics funnel query completed.', [
            'vendor_id' => $normalized['vendor_id'],
            'app' => $normalized['app'],
            'env' => $normalized['env'],
            'rows' => count($validated),
        ]);

        return $validated;
    }

    /**
     * @param array<string,mixed> $param
     *
     * @return list<array<string,mixed>>
     */
    public function runRetention(array $param): array
    {
        $normalized = $this->normalizeRangeParams($param, false);
        $normalized['cohort'] = $this->normalizeRequiredDateString($param, 'cohort');
        $normalized['days'] = $this->normalizeDays($param);

        $rows = $this->repository->fetchRetention(
            $normalized['app'],
            $normalized['env'],
            new \DateTimeImmutable($normalized['cohort']),
            $normalized['days'],
        );
        $validated = $this->validateQueryRows($rows, ['day_offset', 'active_user'], 'retention');

        $this->logger->info('Analytics retention query completed.', [
            'vendor_id' => $normalized['vendor_id'],
            'app' => $normalized['app'],
            'env' => $normalized['env'],
            'rows' => count($validated),
            'days' => $normalized['days'],
        ]);

        return $validated;
    }

    /**
     * @param array<string,mixed> $param
     *
     * @return list<array<string,mixed>>
     */
    public function runCohort(array $param): array
    {
        $normalized = $this->normalizeRangeParams($param, true);
        $steps = $normalized['steps'] ?? throw new \LogicException('Normalized cohort steps are missing.');
        $rows = $this->repository->fetchCohort(
            $normalized['app'],
            $normalized['env'],
            $steps,
            new \DateTimeImmutable($normalized['from']),
            new \DateTimeImmutable($normalized['to']),
        );
        $validated = $this->validateQueryRows($rows, ['cohort_date', 'user_count'], 'cohort');

        $this->logger->info('Analytics cohort query completed.', [
            'vendor_id' => $normalized['vendor_id'],
            'app' => $normalized['app'],
            'env' => $normalized['env'],
            'rows' => count($validated),
        ]);

        return $validated;
    }

    /**
     * @param array<string,mixed> $param
     *
     * @return array{vendor_id:string,app:string,env:string,from:string,to:string,steps?:list<string>}
     */
    private function normalizeRangeParams(array $param, bool $requireSteps): array
    {
        $normalized = [
            'vendor_id' => $this->normalizeNonEmptyString($param, 'vendor_id'),
            'app' => $this->normalizeNonEmptyString($param, 'app'),
            'env' => $this->normalizeNonEmptyString($param, 'env'),
            'from' => $this->normalizeRequiredDateString($param, 'from'),
            'to' => $this->normalizeRequiredDateString($param, 'to'),
        ];

        if ($requireSteps) {
            $normalized['steps'] = $this->normalizeStepList($param['steps'] ?? null);
        }

        if ($normalized['from'] > $normalized['to']) {
            throw new \InvalidArgumentException('from must be earlier than or equal to to.');
        }

        return $normalized;
    }

    /**
     * @param array<string,mixed> $param
     */
    private function normalizeNonEmptyString(array $param, string $field): string
    {
        $raw = $param[$field] ?? '';
        $value = is_scalar($raw) ? trim((string) $raw) : '';
        if ('' === $value) {
            throw new \InvalidArgumentException(sprintf('%s must be a non-empty string.', $field));
        }

        return $value;
    }

    /**
     * @param array<string,mixed> $param
     */
    private function normalizeRequiredDateString(array $param, string $field): string
    {
        $raw = $param[$field] ?? '';
        $value = is_scalar($raw) ? trim((string) $raw) : '';
        if ('' === $value) {
            throw new \InvalidArgumentException(sprintf('%s must be a non-empty date/time string.', $field));
        }

        try {
            return (new \DateTimeImmutable($value))->format('Y-m-d H:i:s');
        } catch (\Throwable $exception) {
            throw new \InvalidArgumentException(sprintf('%s must be a valid date/time string.', $field), 0, $exception);
        }
    }

    /**
     * @param array<string,mixed> $param
     */
    private function normalizeDays(array $param): int
    {
        if (!array_key_exists('days', $param)) {
            throw new \InvalidArgumentException('days must be provided.');
        }

        $value = filter_var($param['days'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!is_int($value)) {
            throw new \InvalidArgumentException('days must be a positive integer.');
        }

        return $value;
    }

    /**
     * @return list<string>
     */
    private function normalizeStepList(mixed $steps): array
    {
        if (!is_array($steps)) {
            throw new \InvalidArgumentException('steps must be a non-empty array.');
        }

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

    /**
     * @param list<array<string,mixed>> $rows
     * @param list<string>              $requiredFields
     *
     * @return list<array<string,mixed>>
     */
    private function validateQueryRows(array $rows, array $requiredFields, string $operation): array
    {
        $validated = [];

        foreach ($rows as $index => $row) {
            foreach ($requiredFields as $field) {
                if (!array_key_exists($field, $row)) {
                    $this->logger->error('Analytics query returned a row with a missing field.', [
                        'operation' => $operation,
                        'row_index' => $index,
                        'field' => $field,
                    ]);
                    throw new \RuntimeException(sprintf('Analytics %s query returned an incomplete row.', $operation));
                }
            }

            $validated[] = $row;
        }

        return $validated;
    }
}
