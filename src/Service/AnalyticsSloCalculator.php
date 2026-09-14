<?php

declare(strict_types=1);

namespace App\Analysing\Service;

use App\Analysing\ServiceInterface\AnalyticsSloCalculatorInterface;
use Psr\Log\LoggerInterface;

final class AnalyticsSloCalculator implements AnalyticsSloCalculatorInterface
{
    private const int MAX_VALUES = 10000;

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function availability(array $values): float
    {
        if (count($values) > self::MAX_VALUES) {
            $this->logger->warning('Analytics SLO calculator rejected too many values.', [
                'values' => count($values),
                'max_values' => self::MAX_VALUES,
            ]);
            throw new \InvalidArgumentException('SLO calculator values exceed the maximum allowed size.');
        }

        $normalized = [];
        foreach ($values as $index => $value) {
            if (!is_scalar($value) || !is_numeric((string) $value)) {
                $this->logger->warning('Analytics SLO calculator skipped non-numeric value.', [
                    'index' => $index,
                    'type' => get_debug_type($value),
                ]);
                continue;
            }

            $numericValue = (float) $value;
            if (!is_finite($numericValue)) {
                $this->logger->warning('Analytics SLO calculator skipped a non-finite numeric value.', [
                    'index' => $index,
                    'value' => $value,
                ]);
                continue;
            }

            $normalized[] = $numericValue;
        }

        if ([] === $normalized) {
            $this->logger->info('Analytics SLO calculator completed with no usable values.');

            return 0.0;
        }

        $ok = count(array_filter(
            $normalized,
            static fn (float $value): bool => $value >= 1.0,
        ));

        $availability = $ok / count($normalized);
        $this->logger->info('Analytics SLO calculator completed.', [
            'input_values' => count($values),
            'usable_values' => count($normalized),
            'ok_values' => $ok,
            'availability' => $availability,
        ]);

        return $availability;
    }
}
