<?php

declare(strict_types=1);

namespace App\Analysing\Entity\Analytics;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'experiment_metric_daily')]
#[ORM\UniqueConstraint(name: 'uniq_experiment_metric_daily_variant', columns: ['day', 'experiment_key', 'variant_key'])]
#[ORM\Index(name: 'idx_experiment_metric_daily_lookup', columns: ['day', 'experiment_key'])]
class AnalyticsExperimentMetricDailyEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id = 0;

    #[ORM\Column(type: 'string', length: 10)]
    private string $day;

    #[ORM\Column(name: 'experiment_key', type: 'string', length: 128)]
    private string $experimentKey;

    #[ORM\Column(name: 'variant_key', type: 'string', length: 128)]
    private string $variantKey;

    #[ORM\Column(type: 'integer')]
    private int $exposure;

    #[ORM\Column(type: 'integer')]
    private int $conversion;

    #[ORM\Column(name: 'value_sum', type: 'float')]
    private float $valueSum;

    public function __construct(string $day, string $experimentKey, string $variantKey, int $exposure, int $conversion, float $valueSum)
    {
        $this->day = $this->normalizeDay($day);
        $this->experimentKey = $this->normalizeString($experimentKey, 'experimentKey');
        $this->variantKey = $this->normalizeString($variantKey, 'variantKey');
        $this->exposure = $this->normalizeCount($exposure, 'exposure');
        $this->conversion = $this->normalizeCount($conversion, 'conversion');
        $this->valueSum = $this->normalizeFloat($valueSum, 'valueSum');
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getDay(): string
    {
        return $this->day;
    }

    public function getExperimentKey(): string
    {
        return $this->experimentKey;
    }

    public function getVariantKey(): string
    {
        return $this->variantKey;
    }

    public function getExposure(): int
    {
        return $this->exposure;
    }

    public function getConversion(): int
    {
        return $this->conversion;
    }

    public function getValueSum(): float
    {
        return $this->valueSum;
    }

    public function setExposure(int $exposure): void
    {
        $this->exposure = $this->normalizeCount($exposure, 'exposure');
    }

    public function setConversion(int $conversion): void
    {
        $this->conversion = $this->normalizeCount($conversion, 'conversion');
    }

    public function setValueSum(float $valueSum): void
    {
        $this->valueSum = $this->normalizeFloat($valueSum, 'valueSum');
    }

    private function normalizeDay(string $day): string
    {
        $normalized = trim($day);
        if ('' === $normalized || 10 !== strlen($normalized)) {
            throw new \InvalidArgumentException('AnalyticsExperiment metric day must be an ISO date string.');
        }

        return $normalized;
    }

    private function normalizeString(string $value, string $field): string
    {
        $normalized = trim($value);
        if ('' === $normalized) {
            throw new \InvalidArgumentException(sprintf('AnalyticsExperiment metric %s must not be empty.', $field));
        }

        return $normalized;
    }

    private function normalizeCount(int $value, string $field): int
    {
        if ($value < 0) {
            throw new \InvalidArgumentException(sprintf('AnalyticsExperiment metric %s must be zero or greater.', $field));
        }

        return $value;
    }

    private function normalizeFloat(float $value, string $field): float
    {
        if (!is_finite($value)) {
            throw new \InvalidArgumentException(sprintf('AnalyticsExperiment metric %s must be finite.', $field));
        }

        return $value;
    }
}
