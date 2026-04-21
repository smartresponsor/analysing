<?php

declare(strict_types=1);

namespace App\Analysing\Service\Analytics;

use App\Analysing\ServiceInterface\Analytics\SegmentationServiceInterface;
use App\Analysing\ValueObject\Analytics\Dimension;
use App\Analysing\ValueObject\Analytics\Segment;
use Psr\Log\LoggerInterface;

final class SegmentationService implements SegmentationServiceInterface
{
    private const int MAX_IDENTIFIER_LENGTH = 128;
    private const int MAX_ROWS = 10000;

    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function apply(array $rows, Dimension $dim, Segment $seg): array
    {
        if (count($rows) > self::MAX_ROWS) {
            $this->logger->warning('Analytics segmentation rejected too many rows.', [
                'rows' => count($rows),
                'max_rows' => self::MAX_ROWS,
            ]);
            throw new \InvalidArgumentException('Segmentation rows exceed the maximum allowed size.');
        }

        $key = trim($dim->name());
        $code = trim($seg->code());
        $matched = [];
        $skipped = 0;

        if ('' === $key) {
            $this->logger->warning('Analytics segmentation rejected an empty dimension name.');

            throw new \InvalidArgumentException('Segmentation dimension must not be empty.');
        }

        if (strlen($key) > self::MAX_IDENTIFIER_LENGTH) {
            $this->logger->warning('Analytics segmentation rejected an overlong dimension name.', [
                'dimension' => $key,
                'max_length' => self::MAX_IDENTIFIER_LENGTH,
            ]);

            throw new \InvalidArgumentException('Segmentation dimension exceeds the maximum allowed length.');
        }

        if ('' === $code) {
            $this->logger->warning('Analytics segmentation rejected an empty segment code.', [
                'dimension' => $key,
            ]);

            throw new \InvalidArgumentException('Segmentation code must not be empty.');
        }

        if (strlen($code) > self::MAX_IDENTIFIER_LENGTH) {
            $this->logger->warning('Analytics segmentation rejected an overlong segment code.', [
                'segment' => $code,
                'max_length' => self::MAX_IDENTIFIER_LENGTH,
            ]);

            throw new \InvalidArgumentException('Segmentation code exceeds the maximum allowed length.');
        }

        foreach ($rows as $index => $row) {
            if (!is_array($row)) {
                ++$skipped;
                $this->logger->warning('Analytics segmentation ignored a non-array row.', [
                    'row_index' => $index,
                    'row_type' => get_debug_type($row),
                ]);
                continue;
            }

            if (!array_key_exists($key, $row)) {
                ++$skipped;
                $this->logger->warning('Analytics segmentation ignored a row missing the requested dimension.', [
                    'row_index' => $index,
                    'dimension' => $key,
                ]);
                continue;
            }

            $value = $row[$key];
            if (!is_scalar($value) && null !== $value) {
                ++$skipped;
                $this->logger->warning('Analytics segmentation ignored a row with a non-scalar dimension value.', [
                    'row_index' => $index,
                    'dimension' => $key,
                    'value_type' => get_debug_type($value),
                ]);
                continue;
            }

            if (trim((string) $value) === $code) {
                $matched[] = $row;
            }
        }

        $this->logger->info('Analytics segmentation completed.', [
            'dimension' => $key,
            'segment' => $code,
            'matched_rows' => count($matched),
            'rows' => count($rows),
            'skipped_rows' => $skipped,
        ]);

        return array_values($matched);
    }
}
