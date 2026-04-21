<?php

declare(strict_types=1);

namespace App\Analysing\Service\Analytics;

use App\Analysing\ServiceInterface\Analytics\WindowQueryInterface;
use Psr\Log\LoggerInterface;

final class WindowQuery implements WindowQueryInterface
{
    private const int MAX_WINDOW_SIZE = 10000;
    private const int MAX_ROWS = 100000;

    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function window(array $rows, int $size): array
    {
        if ($size <= 0) {
            $this->logger->warning('Analytics window query rejected a non-positive window size.', [
                'size' => $size,
            ]);

            throw new \InvalidArgumentException('Window size must be positive.');
        }

        if ($size > self::MAX_WINDOW_SIZE) {
            $this->logger->warning('Analytics window query rejected an overlarge window size.', [
                'size' => $size,
                'max_size' => self::MAX_WINDOW_SIZE,
            ]);

            throw new \InvalidArgumentException('Window size exceeds the maximum allowed size.');
        }

        if (count($rows) > self::MAX_ROWS) {
            $this->logger->warning('Analytics window query rejected too many rows.', [
                'rows' => count($rows),
                'max_rows' => self::MAX_ROWS,
            ]);

            throw new \InvalidArgumentException('Window query rows exceed the maximum allowed size.');
        }

        $out = [];
        $count = count($rows);

        for ($offset = 0; $offset < $count; $offset += $size) {
            $out[] = array_slice($rows, $offset, $size);
        }

        $this->logger->info('Analytics window query completed.', [
            'rows' => $count,
            'size' => $size,
            'windows' => count($out),
        ]);

        return $out;
    }
}
