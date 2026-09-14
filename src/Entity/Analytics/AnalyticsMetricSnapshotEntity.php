<?php

declare(strict_types=1);

namespace App\Analysing\Entity\Analytics;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'analytics_metric_snapshot')]
#[ORM\Index(name: 'idx_metric_period', columns: ['metric', 'period_start', 'period_end'])]
class AnalyticsMetricSnapshotEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id = 0;

    #[ORM\Column(type: 'string', length: 128)]
    private string $metric;

    #[ORM\Column(type: 'float')]
    private float $value;

    /** @var array<string,mixed>|null */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $dimensions = null;

    #[ORM\Column(name: 'period_start', type: 'datetime_immutable')]
    private \DateTimeImmutable $periodStart;

    #[ORM\Column(name: 'period_end', type: 'datetime_immutable')]
    private \DateTimeImmutable $periodEnd;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    /** @param array<string,mixed>|null $dimensions */
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
        $this->periodStart = $periodStart;
        $this->periodEnd = $periodEnd;
        $this->dimensions = null === $dimensions ? null : $this->normalizeDimensions($dimensions);
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): int
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

    /** @return array<string,mixed>|null */
    public function getDimensions(): ?array
    {
        return $this->dimensions;
    }

    public function getPeriodStart(): \DateTimeImmutable
    {
        return $this->periodStart;
    }

    public function getPeriodEnd(): \DateTimeImmutable
    {
        return $this->periodEnd;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @param array<string,mixed> $dimensions
     *
     * @return array<string,mixed>
     */
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

            $normalized[trim($key)] = $value;
        }

        return $normalized;
    }
}
