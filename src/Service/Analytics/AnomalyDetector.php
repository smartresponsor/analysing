<?php

declare(strict_types=1);

namespace App\Analysing\Service\Analytics;

use App\Analysing\ServiceInterface\Analytics\AnomalyDetectorInterface;
use Psr\Log\LoggerInterface;

final class AnomalyDetector implements AnomalyDetectorInterface
{
    private const int MAX_VALUES = 10000;

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function zscore(array $values): array
    {
        if (count($values) > self::MAX_VALUES) {
            $this->logger->warning('Analytics anomaly detector rejected too many values.', [
                'values' => count($values),
                'max_values' => self::MAX_VALUES,
            ]);
            throw new \InvalidArgumentException('Anomaly detector values exceed the maximum allowed size.');
        }

        $normalized = [];
        foreach ($values as $index => $value) {
            if (!is_scalar($value) || !is_numeric((string) $value)) {
                $this->logger->warning('Analytics anomaly detector skipped non-numeric value.', [
                    'index' => $index,
                    'type' => get_debug_type($value),
                ]);
                continue;
            }

            $numericValue = (float) $value;
            if (!is_finite($numericValue)) {
                $this->logger->warning('Analytics anomaly detector skipped a non-finite numeric value.', [
                    'index' => $index,
                    'value' => $value,
                ]);
                continue;
            }

            $normalized[] = $numericValue;
        }

        if ([] === $normalized) {
            $this->logger->info('Analytics anomaly detector completed with no usable values.');

            return [];
        }

        $count = count($normalized);
        $mean = array_sum($normalized) / $count;
        $variance = 0.0;
        foreach ($normalized as $value) {
            $variance += ($value - $mean) ** 2;
        }

        $std = sqrt($variance / max(1, $count));
        if (!is_finite($std)) {
            $this->logger->error('Analytics anomaly detector produced non-finite standard deviation.', [
                'count' => $count,
                'mean' => $mean,
                'variance' => $variance,
            ]);
            throw new \RuntimeException('Unable to calculate anomaly z-score.');
        }

        $scores = array_map(
            static fn (float $value): float => $std > 0.0 ? ($value - $mean) / $std : 0.0,
            $normalized,
        );

        $this->logger->info('Analytics anomaly detector completed.', [
            'input_values' => count($values),
            'usable_values' => $count,
            'scores' => count($scores),
            'std' => $std,
        ]);

        return $scores;
    }
}
