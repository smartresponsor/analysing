<?php

declare(strict_types=1);

namespace App\Entity\Analytics;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'analytics_metric_snapshot')]
#[ORM\Index(name: 'idx_metric_period', columns: ['metric', 'period_start', 'period_end'])]
class MetricSnapshot
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 128)]
    private string $metric;

    #[ORM\Column(type: 'float')]
    private float $value;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $dimensions = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $period_start;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $period_end;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $created_at;

    public function __construct(string $metric, float $value, \DateTimeImmutable $periodStart, \DateTimeImmutable $periodEnd, ?array $dimensions = null)
    {
        $normalizedMetric = trim($metric);
        if ('' === $normalizedMetric) {
            throw new \InvalidArgumentException('Metric snapshot metric must not be empty.');
        }
        if (!is_finite($value)) {
            throw new \InvalidArgumentException('Metric snapshot value must be finite.');
        }
        if ($periodStart > $periodEnd) {
            throw new \InvalidArgumentException('Metric snapshot periodStart must be earlier than or equal to periodEnd.');
        }

        $this->metric = $normalizedMetric;
        $this->value = $value;
        $this->period_start = $periodStart;
        $this->period_end = $periodEnd;
        $this->dimensions = null === $dimensions ? null : $this->normalizeDimensions($dimensions);
        $this->created_at = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMetric(): string
    {
        return $this->metric;
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function getDimensions(): ?array
    {
        return $this->dimensions;
    }

    public function getPeriodStart(): \DateTimeImmutable
    {
        return $this->period_start;
    }

    public function getPeriodEnd(): \DateTimeImmutable
    {
        return $this->period_end;
    }

    private function normalizeDimensions(array $dimensions): array
    {
        $normalized = [];
        foreach ($dimensions as $key => $value) {
            if (!is_string($key) || '' === trim($key)) {
                throw new \InvalidArgumentException('Metric snapshot dimension keys must be non-empty strings.');
            }

            if (!is_scalar($value) && null !== $value) {
                throw new \InvalidArgumentException('Metric snapshot dimension values must be scalar or null.');
            }

            $normalized[$key] = $value;
        }

        return $normalized;
    }
}
