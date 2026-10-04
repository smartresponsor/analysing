<?php

declare(strict_types=1);

namespace App\Analysing\Service;

use App\Analysing\ServiceInterface\AnalyticsSegmentationServiceInterface;
use App\Analysing\ValueObject\AnalyticsDimension;
use App\Analysing\ValueObject\AnalyticsSegment;
use Psr\Log\LoggerInterface;

final class AnalyticsSegmentationService implements AnalyticsSegmentationServiceInterface
{
    private const int MAX_IDENTIFIER_LENGTH = 128;
    private const int MAX_ROWS = 10000;

    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function apply(array $rows, AnalyticsDimension $dim, AnalyticsSegment $seg): array
    {
        $this->assertRowLimit($rows);

        $key = $this->normalizeDimension($dim);
        $code = $this->normalizeSegment($seg, $key);
        $matched = [];
        $skipped = 0;

        foreach ($rows as $index => $row) {
            $match = $this->matchingRow($row, $index, $key, $code, $skipped);
            if (null !== $match) {
                $matched[] = $match;
            }
        }

        $this->logger->info('Analytics segmentation completed.', [
            'dimension' => $key,
            'segment' => $code,
            'matched_rows' => count($matched),
            'rows' => count($rows),
            'skipped_rows' => $skipped,
        ]);

        return $matched;
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function assertRowLimit(array $rows): void
    {
        if (count($rows) <= self::MAX_ROWS) {
            return;
        }

        $this->logger->warning('Analytics segmentation rejected too many rows.', [
            'rows' => count($rows),
            'max_rows' => self::MAX_ROWS,
        ]);

        throw new \InvalidArgumentException('Segmentation rows exceed the maximum allowed size.');
    }

    private function normalizeDimension(AnalyticsDimension $dim): string
    {
        $key = trim($dim->name());
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

        return $key;
    }

    private function normalizeSegment(AnalyticsSegment $seg, string $key): string
    {
        $code = trim($seg->code());
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

        return $code;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function matchingRow(mixed $row, int|string $index, string $key, string $code, int &$skipped): ?array
    {
        if (!is_array($row)) {
            ++$skipped;
            $this->logger->warning('Analytics segmentation ignored a non-array row.', [
                'row_index' => $index,
                'row_type' => get_debug_type($row),
            ]);

            return null;
        }

        /** @var array<string, mixed> $row */
        if (!array_key_exists($key, $row)) {
            ++$skipped;
            $this->logger->warning('Analytics segmentation ignored a row missing the requested dimension.', [
                'row_index' => $index,
                'dimension' => $key,
            ]);

            return null;
        }

        $value = $row[$key];
        if (!is_scalar($value) && null !== $value) {
            ++$skipped;
            $this->logger->warning('Analytics segmentation ignored a row with a non-scalar dimension value.', [
                'row_index' => $index,
                'dimension' => $key,
                'value_type' => get_debug_type($value),
            ]);

            return null;
        }

        return trim((string) $value) === $code ? $row : null;
    }
}
