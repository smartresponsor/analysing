<?php

declare(strict_types=1);

namespace App\Analysing\Service;

use App\Analysing\ServiceInterface\AnalyticsVendorScopeInterface;
use App\Analysing\ValueObject\AnalyticsVendorId;
use Psr\Log\LoggerInterface;

final class AnalyticsVendorScope implements AnalyticsVendorScopeInterface
{
    private const int MAX_VENDOR_LENGTH = 128;

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function filter(array $rows, AnalyticsVendorId $vendor): array
    {
        $vendorValue = $this->validateVendor($vendor);
        $filtered = [];
        $skipped = 0;

        foreach ($rows as $index => $row) {
            $rowVendor = $this->resolveRowVendor($row, $index);
            if (null === $rowVendor) {
                ++$skipped;
                continue;
            }

            if ($rowVendor === $vendorValue) {
                $filtered[] = $row;
            }
        }

        $this->logger->info('Analytics vendor scope completed.', [
            'vendor' => $vendorValue,
            'rows' => count($rows),
            'matched_rows' => count($filtered),
            'skipped_rows' => $skipped,
        ]);

        return $filtered;
    }

    private function validateVendor(AnalyticsVendorId $vendor): string
    {
        $vendorValue = trim($vendor->value());
        if ('' === $vendorValue) {
            $this->logger->warning('Analytics vendor scope rejected an empty vendor identifier.');
            throw new \InvalidArgumentException('Vendor scope requires a non-empty vendor identifier.');
        }

        if (strlen($vendorValue) > self::MAX_VENDOR_LENGTH) {
            $this->logger->warning('Analytics vendor scope rejected an overlong vendor identifier.', [
                'vendor_length' => strlen($vendorValue),
                'max_length' => self::MAX_VENDOR_LENGTH,
            ]);
            throw new \InvalidArgumentException('Vendor identifier exceeds the maximum allowed length.');
        }

        return $vendorValue;
    }

    private function resolveRowVendor(mixed $row, int|string $index): ?string
    {
        if (!is_array($row)) {
            $this->logger->warning('Analytics vendor scope skipped non-array row.', [
                'index' => $index,
                'type' => get_debug_type($row),
            ]);

            return null;
        }

        $rowVendorValue = $row['vendor'] ?? '';
        $rowVendor = is_scalar($rowVendorValue) ? trim((string) $rowVendorValue) : '';
        if ('' === $rowVendor) {
            $this->logger->warning('Analytics vendor scope skipped row without vendor.', ['index' => $index]);

            return null;
        }

        if (strlen($rowVendor) > self::MAX_VENDOR_LENGTH) {
            $this->logger->warning('Analytics vendor scope skipped row with an overlong vendor value.', [
                'index' => $index,
                'vendor_length' => strlen($rowVendor),
                'max_length' => self::MAX_VENDOR_LENGTH,
            ]);

            return null;
        }

        return $rowVendor;
    }
}
