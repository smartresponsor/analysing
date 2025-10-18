<?php declare(strict_types=1);
namespace App\Entity\Analytics;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'analytics_metric_snapshot')]
#[ORM\Index(name: 'idx_metric_period', columns: ['metric', 'period_start', 'period_end'])]
class MetricSnapshot
{
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column(type: 'integer')] private ?int $id = null;
    #[ORM\Column(type: 'string', length: 128)] private string $metric;
    #[ORM\Column(type: 'float')] private float $value;
    #[ORM\Column(type: 'json', nullable: true)] private ?array $dimensions = null;
    #[ORM\Column(type: 'datetime_immutable')] private \DateTimeImmutable $period_start;
    #[ORM\Column(type: 'datetime_immutable')] private \DateTimeImmutable $period_end;
    #[ORM\Column(type: 'datetime_immutable')] private \DateTimeImmutable $created_at;

    public function __construct(string $metric, float $value, \DateTimeImmutable $periodStart, \DateTimeImmutable $periodEnd, ?array $dimensions = null)
    { $this->metric=$metric; $this->value=$value; $this->period_start=$periodStart; $this->period_end=$periodEnd; $this->dimensions=$dimensions; $this->created_at=new \DateTimeImmutable('now'); }

    public function getId(): ?int { return $this->id; } public function getMetric(): string { return $this->metric; }
    public function getValue(): float { return $this->value; } public function getDimensions(): ?array { return $this->dimensions; }
    public function getPeriodStart(): \DateTimeImmutable { return $this->period_start; } public function getPeriodEnd(): \DateTimeImmutable { return $this->period_end; }
}
