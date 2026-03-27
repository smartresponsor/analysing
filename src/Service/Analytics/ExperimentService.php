<?php

declare(strict_types=1);

namespace App\Service\Analytics;

use App\ServiceInterface\Analytics\ExperimentServiceInterface;
use Psr\Log\LoggerInterface;

final class ExperimentService implements ExperimentServiceInterface
{
    private const MAX_IDENTIFIER_LENGTH = 128;
    private const MAX_VARIANTS_PER_EXPERIMENT = 32;
    private const MAX_RECORDED_EVENTS = 1000;

    /** @var array<string, array<string, int>> */
    private array $weight = [];

    /** @var list<array<string, scalar>> */
    private array $recorded = [];

    /**
     * @param array<string, array<string, int|float|string|bool|null>> $weightMap
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        array $weightMap = [],
    ) {
        $this->weight = $this->normalizeWeightMap($weightMap);
    }

    public function choose(string $experimentKey, string $subjectId): string
    {
        $experimentKey = $this->normalizeNonEmptyString($experimentKey, 'experimentKey');
        $subjectId = $this->normalizeNonEmptyString($subjectId, 'subjectId');

        $map = $this->weight[$experimentKey] ?? ['A' => 1];
        if ([] === $map) {
            $this->logger->warning('Analytics experiment service fell back to default variant because weight map is empty.', [
                'experiment_key' => $experimentKey,
            ]);

            return 'A';
        }

        $total = array_sum($map);
        if ($total <= 0) {
            $fallback = array_key_first($map) ?? 'A';
            $this->logger->warning('Analytics experiment service fell back because weight total is not positive.', [
                'experiment_key' => $experimentKey,
                'fallback_variant' => $fallback,
            ]);

            return $fallback;
        }

        $hash = hexdec(substr(hash('sha256', $subjectId.'|'.$experimentKey), 0, 8));
        $pick = $hash % $total;

        foreach ($map as $key => $weight) {
            if ($pick < $weight) {
                return $key;
            }
            $pick -= $weight;
        }

        return array_key_first($map) ?? 'A';
    }

    public function record(string $experimentKey, string $variantKey, string $metric, float $value = 1.0): void
    {
        $experimentKey = $this->normalizeNonEmptyString($experimentKey, 'experimentKey');
        $variantKey = $this->normalizeNonEmptyString($variantKey, 'variantKey');
        $metric = $this->normalizeNonEmptyString($metric, 'metric');

        if (!is_finite($value)) {
            throw new \InvalidArgumentException('value must be a finite float.');
        }

        $this->recorded[] = [
            'experiment_key' => $experimentKey,
            'variant_key' => $variantKey,
            'metric' => $metric,
            'value' => $value,
        ];
        $this->trimRecordedIfNeeded();

        $this->logger->info('Analytics experiment metric recorded in in-memory buffer.', [
            'experiment_key' => $experimentKey,
            'variant_key' => $variantKey,
            'metric' => $metric,
            'value' => $value,
            'buffer_size' => count($this->recorded),
        ]);
    }

    /**
     * @param array<string, array<string, int|float|string|bool|null>> $weightMap
     *
     * @return array<string, array<string, int>>
     */
    private function normalizeWeightMap(array $weightMap): array
    {
        $normalized = [];

        foreach ($weightMap as $experimentKey => $rawVariants) {
            $normalizedExperimentKey = trim((string) $experimentKey);
            if ('' === $normalizedExperimentKey) {
                $this->logger->warning('Analytics experiment service ignored an empty experiment key in weight map.');
                continue;
            }

            if (!is_array($rawVariants)) {
                $this->logger->warning('Analytics experiment service ignored a non-array variant weight map.', [
                    'experiment_key' => $normalizedExperimentKey,
                    'value_type' => get_debug_type($rawVariants),
                ]);
                continue;
            }

            $variants = [];
            foreach ($rawVariants as $variantKey => $rawWeight) {
                if (count($variants) >= self::MAX_VARIANTS_PER_EXPERIMENT) {
                    $this->logger->warning('Analytics experiment service truncated variants because the maximum count was reached.', [
                        'experiment_key' => $normalizedExperimentKey,
                        'max_variants' => self::MAX_VARIANTS_PER_EXPERIMENT,
                    ]);
                    break;
                }

                $normalizedVariantKey = trim((string) $variantKey);
                if ('' !== $normalizedVariantKey && strlen($normalizedVariantKey) > self::MAX_IDENTIFIER_LENGTH) {
                    $this->logger->warning('Analytics experiment service ignored an overlong variant key.', [
                        'experiment_key' => $normalizedExperimentKey,
                        'variant_key' => $normalizedVariantKey,
                        'max_length' => self::MAX_IDENTIFIER_LENGTH,
                    ]);
                    continue;
                }
                $weight = filter_var($rawWeight, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                if ('' === $normalizedVariantKey || !is_int($weight)) {
                    $this->logger->warning('Analytics experiment service ignored an invalid variant weight entry.', [
                        'experiment_key' => $normalizedExperimentKey,
                        'variant_key' => $normalizedVariantKey,
                        'raw_weight' => $rawWeight,
                    ]);
                    continue;
                }

                $variants[$normalizedVariantKey] = $weight;
            }

            if ([] === $variants) {
                $this->logger->warning('Analytics experiment service ignored an empty normalized variant map.', [
                    'experiment_key' => $normalizedExperimentKey,
                ]);
                continue;
            }

            $normalized[$normalizedExperimentKey] = $variants;
        }

        return $normalized;
    }

    private function normalizeNonEmptyString(string $value, string $field): string
    {
        $normalized = trim($value);
        if ('' === $normalized) {
            throw new \InvalidArgumentException(sprintf('%s must be a non-empty string.', $field));
        }

        if (strlen($normalized) > self::MAX_IDENTIFIER_LENGTH) {
            throw new \InvalidArgumentException(sprintf('%s exceeds the maximum supported length.', $field));
        }

        return $normalized;
    }

    private function trimRecordedIfNeeded(): void
    {
        while (count($this->recorded) > self::MAX_RECORDED_EVENTS) {
            array_shift($this->recorded);
            $this->logger->warning('Analytics experiment service evicted the oldest recorded event because the buffer reached its maximum size.', [
                'max_events' => self::MAX_RECORDED_EVENTS,
            ]);
        }
    }
}
