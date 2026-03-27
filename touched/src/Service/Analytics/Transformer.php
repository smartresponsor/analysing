<?php
declare(strict_types=1);

namespace App\Service\Analytics;

use App\ServiceInterface\Analytics\TransformerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

final class Transformer implements TransformerInterface
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function map(array $rows, callable $fn): array
    {
        $mapped = [];

        foreach ($rows as $index => $row) {
            try {
                $mapped[] = $fn($row);
            } catch (Throwable $exception) {
                $this->logger->error('Analytics transformer callable failed for a row.', [
                    'exception' => $exception,
                    'row_index' => $index,
                ]);

                throw new RuntimeException('Analytics transformer failed for row ' . $index . '.', 0, $exception);
            }
        }

        return $mapped;
    }
}
