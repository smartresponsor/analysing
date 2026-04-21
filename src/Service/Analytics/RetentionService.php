<?php

declare(strict_types=1);

namespace App\Analysing\Service\Analytics;

use App\Analysing\ServiceInterface\Analytics\RetentionServiceInterface;
use Psr\Log\LoggerInterface;

final readonly class RetentionService implements RetentionServiceInterface
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function prune(array $rows, int $maxDays): array
    {
        if ($maxDays <= 0) {
            $this->logger->warning('Analytics retention prune rejected because maxDays is not positive.', [
                'max_days' => $maxDays,
            ]);

            throw new \InvalidArgumentException('Retention maxDays must be positive.');
        }

        $border = time() - ($maxDays * 86400);
        $pruned = [];

        foreach ($rows as $index => $row) {
            if (!is_array($row)) {
                $this->logger->warning('Analytics retention prune ignored a non-array row.', [
                    'row_index' => $index,
                    'row_type' => get_debug_type($row),
                ]);
                continue;
            }

            $timestamp = $row['ts'] ?? null;
            if (!is_int($timestamp) && !(is_string($timestamp) && 1 === preg_match('/^-?\d+$/', $timestamp))) {
                $this->logger->warning('Analytics retention prune ignored a row with invalid ts.', [
                    'row_index' => $index,
                    'ts' => $timestamp,
                ]);
                continue;
            }

            if ((int) $timestamp >= $border) {
                $pruned[] = $row;
            }
        }

        return array_values($pruned);
    }
}
