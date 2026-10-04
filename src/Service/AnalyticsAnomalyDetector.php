<?php

declare(strict_types=1);

namespace App\Analysing\Service;

use App\Analysing\ServiceInterface\AnalyticsAnomalyDetectorInterface;
use Psr\Log\LoggerInterface;

final class AnalyticsAnomalyDetector implements AnalyticsAnomalyDetectorInterface
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

        $normalized = $this->normalizeValues($values);
        if ([] === $normalized) {
            $this->logger->info('Analytics anomaly detector completed with no usable values.');

            return [];
        }

        $count = count($normalized);
        $mean = array_sum($normalized) / $count;
        [$std, $variance] = $this->calculateStandardDeviation($normalized, $mean);
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

    /**
     * @param list<mixed> $values
     *
     * @return list<float>
     */
    private function normalizeValues(array $values): array
    {
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

        return $normalized;
    }

    /**
     * @param list<float> $values
     *
     * @return array{0: float, 1: float}
     */
    private function calculateStandardDeviation(array $values, float $mean): array
    {
        $variance = 0.0;
        foreach ($values as $value) {
            $variance += ($value - $mean) ** 2;
        }

        return [sqrt($variance / max(1, count($values))), $variance];
    }
}
