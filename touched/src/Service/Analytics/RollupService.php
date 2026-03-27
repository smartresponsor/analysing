<?php
declare(strict_types=1);

namespace App\Service\Analytics;

use App\ServiceInterface\Analytics\RollupServiceInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

final class RollupService implements RollupServiceInterface
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function sum(array $rows, string $field): float|int
    {
        $normalizedField = trim($field);
        if ($normalizedField === '') {
            $this->logger->warning('Analytics rollup rejected an empty field name.');
            throw new InvalidArgumentException('Rollup field must not be empty.');
        }

        $total = 0.0;

        foreach ($rows as $index => $row) {
            if (!is_array($row)) {
                $this->logger->warning('Analytics rollup ignored a non-array row.', [
                    'row_index' => $index,
                    'row_type' => get_debug_type($row),
                ]);
                continue;
            }

            $value = $row[$normalizedField] ?? 0;
            if (is_array($value) || is_object($value)) {
                $this->logger->warning('Analytics rollup ignored a non-scalar field value.', [
                    'row_index' => $index,
                    'field' => $normalizedField,
                    'value_type' => get_debug_type($value),
                ]);
                continue;
            }

            $total += (float) $value;
        }

        return $total;
    }
}
