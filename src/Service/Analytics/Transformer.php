<?php

declare(strict_types=1);

namespace App\Service\Analytics;

use App\ServiceInterface\Analytics\TransformerInterface;
use Psr\Log\LoggerInterface;

final class Transformer implements TransformerInterface
{
    private const int MAX_ROWS = 10000;

    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function map(array $rows, callable $fn): array
    {
        if (count($rows) > self::MAX_ROWS) {
            $this->logger->warning('Analytics transformer rejected too many rows.', [
                'rows' => count($rows),
                'max_rows' => self::MAX_ROWS,
            ]);
            throw new \InvalidArgumentException('Transformer rows exceed the maximum allowed size.');
        }

        $mapped = [];

        foreach ($rows as $index => $row) {
            try {
                $mapped[] = $fn($row);
            } catch (\Throwable $exception) {
                $this->logger->error('Analytics transformer callable failed for a row.', [
                    'exception' => $exception,
                    'row_index' => $index,
                ]);

                throw new \RuntimeException('Analytics transformer failed for row '.$index.'.', 0, $exception);
            }
        }

        $this->logger->info('Analytics transformer completed.', [
            'rows' => count($rows),
            'mapped_rows' => count($mapped),
        ]);

        return $mapped;
    }
}
