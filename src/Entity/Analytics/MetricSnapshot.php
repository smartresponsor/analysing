<?php
declare(strict_types=1);

namespace App\Entity\Analytics;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'metric_snapshot')]
class MetricSnapshot
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'integer')]
    private int $vendorId;

    #[ORM\Column(length: 3)]
    private string $currency;

    #[ORM\Column(type: 'bigint')]
    private int $grossMinor;

    #[ORM\Column(type: 'bigint')]
    private int $netMinor;

    #[ORM\Column(type: 'decimal', precision: 6, scale: 2)]
    private string $riskScore;

    #[ORM\Column(type: 'date_immutable')]
    private DateTimeImmutable $date;

    public function __construct(int $vendorId, string $currency, int $grossMinor, int $netMinor, string $riskScore)
    {
        $this->vendorId = $vendorId;
        $this->currency = strtoupper($currency);
        $this->grossMinor = $grossMinor;
        $this->netMinor = $netMinor;
        $this->riskScore = $riskScore;
        $this->date = new DateTimeImmutable('today');
    }
}
