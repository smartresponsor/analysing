<?php
declare(strict_types=1);

namespace App\Service\Analytics;

use App\ServiceInterface\Analytics\SegmentationServiceInterface;
use App\ValueObject\Analytics\Dimension;
use App\ValueObject\Analytics\Segment;
use Psr\Log\LoggerInterface;

final class SegmentationService implements SegmentationServiceInterface
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function apply(array $rows, Dimension $dim, Segment $seg): array
    {
        $key = $dim->name();
        $code = $seg->code();
        $matched = [];

        foreach ($rows as $index => $row) {
            if (!is_array($row)) {
                $this->logger->warning('Analytics segmentation ignored a non-array row.', [
                    'row_index' => $index,
                    'row_type' => get_debug_type($row),
                ]);
                continue;
            }

            if (($row[$key] ?? null) === $code) {
                $matched[] = $row;
            }
        }

        return array_values($matched);
    }
}
