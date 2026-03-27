<?php
declare(strict_types=1);

namespace App\Service\Analytics;

use App\ServiceInterface\Analytics\WindowQueryInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

final class WindowQuery implements WindowQueryInterface
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function window(array $rows, int $size): array
    {
        if ($size <= 0) {
            $this->logger->warning('Analytics window query rejected a non-positive window size.', [
                'size' => $size,
            ]);

            throw new InvalidArgumentException('Window size must be positive.');
        }

        $out = [];
        $count = count($rows);

        for ($offset = 0; $offset < $count; $offset += $size) {
            $out[] = array_slice($rows, $offset, $size);
        }

        return $out;
    }
}
