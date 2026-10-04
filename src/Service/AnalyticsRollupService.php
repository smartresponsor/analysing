<?php

declare(strict_types=1);

namespace App\Analysing\Service;

use App\Analysing\ServiceInterface\AnalyticsRollupServiceInterface;
use Psr\Log\LoggerInterface;

final class AnalyticsRollupService implements AnalyticsRollupServiceInterface
{
    private const int MAX_FIELD_LENGTH = 128;
    private const int MAX_ROWS = 10000;

    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function sum(array $rows, string $field): float|int
    {
        $normalizedField = $this->normalizeField($field);
        $this->assertRowLimit(count($rows));

        $total = 0.0;
        $included = 0;

        foreach ($rows as $index => $row) {
            if (!is_array($row)) {
                $this->logger->warning('Analytics rollup ignored a non-array row.', [
                    'row_index' => $index,
                    'row_type' => get_debug_type($row),
                ]);
                continue;
            }

            $numericValue = $this->numericValue($row[$normalizedField] ?? 0, $normalizedField, $index);
            if (null === $numericValue) {
                continue;
            }

            $total += $numericValue;
            if (!is_finite($total)) {
                $this->logger->error('Analytics rollup produced a non-finite total.', [
                    'field' => $normalizedField,
                    'row_index' => $index,
                    'partial_total' => $total,
                ]);
                throw new \RuntimeException('Rollup total became non-finite.');
            }

            ++$included;
        }

        $this->logger->info('Analytics rollup completed.', [
            'field' => $normalizedField,
            'rows' => count($rows),
            'included_rows' => $included,
            'total' => $total,
        ]);

        if ($total === (float) (int) $total) {
            return (int) $total;
        }

        return $total;
    }

    private function normalizeField(string $field): string
    {
        $normalizedField = trim($field);
        if ('' === $normalizedField) {
            $this->logger->warning('Analytics rollup rejected an empty field name.');
            throw new \InvalidArgumentException('Rollup field must not be empty.');
        }

        if (strlen($normalizedField) > self::MAX_FIELD_LENGTH) {
            $this->logger->warning('Analytics rollup rejected an overlong field name.', [
                'field' => $normalizedField,
                'max_length' => self::MAX_FIELD_LENGTH,
            ]);
            throw new \InvalidArgumentException('Rollup field exceeds the maximum allowed length.');
        }

        return $normalizedField;
    }

    private function assertRowLimit(int $rowCount): void
    {
        if ($rowCount <= self::MAX_ROWS) {
            return;
        }

        $this->logger->warning('Analytics rollup rejected too many rows.', [
            'rows' => $rowCount,
            'max_rows' => self::MAX_ROWS,
        ]);
        throw new \InvalidArgumentException('Rollup rows exceed the maximum allowed size.');
    }

    private function numericValue(mixed $value, string $field, int|string $rowIndex): ?float
    {
        if (is_array($value) || is_object($value)) {
            $this->logger->warning('Analytics rollup ignored a non-scalar field value.', [
                'row_index' => $rowIndex,
                'field' => $field,
                'value_type' => get_debug_type($value),
            ]);

            return null;
        }

        if ('' === $value) {
            return null;
        }

        if (!is_numeric($value)) {
            $this->logger->warning('Analytics rollup ignored a non-numeric field value.', [
                'row_index' => $rowIndex,
                'field' => $field,
                'value' => $value,
            ]);

            return null;
        }

        $numericValue = (float) $value;
        if (is_finite($numericValue)) {
            return $numericValue;
        }

        $this->logger->warning('Analytics rollup ignored a non-finite field value.', [
            'row_index' => $rowIndex,
            'field' => $field,
            'value' => $value,
        ]);

        return null;
    }
}
