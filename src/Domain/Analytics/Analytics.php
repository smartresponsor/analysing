<?php

/*
 * Marketing America Corp. Oleksandr Tishchenko
 * Author: Oleksandr Tishchenko <dev@highhopesamerica.com>
 */

declare(strict_types=1);

namespace App\Analysing\Domain\Analytics;

use App\Analysing\DomainInterface\Analytics\AnalyticsInterface;
use App\Analysing\DomainInterface\Analytics\ClickhouseClientInterface;
use Psr\Log\LoggerInterface;

final readonly class Analytics implements AnalyticsInterface
{
    public function __construct(
        private ClickhouseClientInterface $client,
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
        $rows = $this->client->query($this->loadQuery('funnel.sql'), $normalized);
        $validated = $this->validateQueryRows($rows, ['day', 'user_count'], 'funnel');

        $this->logger->info('Analytics funnel query completed.', [
            'tenant_id' => $normalized['tenant_id'],
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

        $rows = $this->client->query($this->loadQuery('retention.sql'), $normalized);
        $validated = $this->validateQueryRows($rows, ['day_offset', 'active_user'], 'retention');

        $this->logger->info('Analytics retention query completed.', [
            'tenant_id' => $normalized['tenant_id'],
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
        $rows = $this->client->query($this->loadQuery('cohort.sql'), $normalized);
        $validated = $this->validateQueryRows($rows, ['cohort_date', 'user_count'], 'cohort');

        $this->logger->info('Analytics cohort query completed.', [
            'tenant_id' => $normalized['tenant_id'],
            'app' => $normalized['app'],
            'env' => $normalized['env'],
            'rows' => count($validated),
        ]);

        return $validated;
    }

    /**
     * @param array<string,mixed> $param
     *
     * @return array<string,int|string>
     */
    private function normalizeRangeParams(array $param, bool $requireSteps): array
    {
        $normalized = [
            'tenant_id' => $this->normalizeNonEmptyString($param, 'tenant_id'),
            'app' => $this->normalizeNonEmptyString($param, 'app'),
            'env' => $this->normalizeNonEmptyString($param, 'env'),
            'from' => $this->normalizeRequiredDateString($param, 'from'),
            'to' => $this->normalizeRequiredDateString($param, 'to'),
        ];

        if ($requireSteps) {
            $steps = $this->normalizeStepList($param['steps'] ?? null);
            $normalized += $this->buildStepBindings($steps);
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
     * @param list<string> $steps
     *
     * @return array<string, int|string>
     */
    private function buildStepBindings(array $steps): array
    {
        $bindings = ['step_count' => count($steps)];

        foreach ($steps as $index => $step) {
            $bindings['step_'.($index + 1)] = $step;
        }

        for ($index = count($steps) + 1; $index <= 4; ++$index) {
            $bindings['step_'.$index] = '';
        }

        if (
            !isset($bindings['step_count'], $bindings['step_1'], $bindings['step_2'], $bindings['step_3'], $bindings['step_4'])
        ) {
            throw new \RuntimeException('Analytics step bindings are incomplete.');
        }

        return $bindings;
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
            if (!is_array($row)) {
                $this->logger->error('Analytics query returned a non-array row.', [
                    'operation' => $operation,
                    'row_index' => $index,
                    'row_type' => get_debug_type($row),
                ]);
                throw new \RuntimeException(sprintf('Analytics %s query returned an invalid row.', $operation));
            }

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

    private function loadQuery(string $relativePath): string
    {
        $path = __DIR__.'/../../queries/'.$relativePath;
        if (!is_file($path)) {
            $this->logger->error('Analytics query file is missing.', ['path' => $path]);
            throw new \RuntimeException('Analytics query file is missing: '.$relativePath);
        }

        $sql = file_get_contents($path);
        if (false === $sql || '' === trim($sql)) {
            $this->logger->error('Analytics query file is unreadable or empty.', ['path' => $path]);
            throw new \RuntimeException('Analytics query file is unreadable or empty: '.$relativePath);
        }

        return $sql;
    }
}
