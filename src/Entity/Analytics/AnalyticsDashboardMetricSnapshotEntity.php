<?php

declare(strict_types=1);

namespace App\Analysing\Entity\Analytics;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'metric_snapshot')]
#[ORM\Index(name: 'idx_metric_snapshot_vendor_currency_date', columns: ['vendor_id', 'currency', 'date'])]
class AnalyticsDashboardMetricSnapshotEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id = 0;

    #[ORM\Column(name: 'date', type: 'datetime_immutable')]
    private \DateTimeImmutable $snapshotDate;

    #[ORM\Column(name: 'vendor_id', type: 'integer')]
    private int $vendorId;

    #[ORM\Column(type: 'string', length: 3)]
    private string $currency;

    #[ORM\Column(name: 'gross_minor', type: 'integer')]
    private int $grossMinor;

    #[ORM\Column(name: 'net_minor', type: 'integer')]
    private int $netMinor;

    public function __construct(int $vendorId, string $currency, \DateTimeImmutable $snapshotDate, int $grossMinor, int $netMinor)
    {
        if ($vendorId <= 0) {
            throw new \InvalidArgumentException('Dashboard snapshot vendorId must be a positive integer.');
        }

        $normalizedCurrency = strtoupper(trim($currency));
        if (1 !== preg_match('/^[A-Z]{3}$/', $normalizedCurrency)) {
            throw new \InvalidArgumentException('Dashboard snapshot currency must be a 3-letter ISO code.');
        }

        if ($grossMinor < 0) {
            throw new \InvalidArgumentException('Dashboard snapshot grossMinor must be zero or greater.');
        }

        if ($netMinor < 0) {
            throw new \InvalidArgumentException('Dashboard snapshot netMinor must be zero or greater.');
        }

        $this->vendorId = $vendorId;
        $this->currency = $normalizedCurrency;
        $this->snapshotDate = $snapshotDate;
        $this->grossMinor = $grossMinor;
        $this->netMinor = $netMinor;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getSnapshotDate(): \DateTimeImmutable
    {
        return $this->snapshotDate;
    }

    public function getVendorId(): int
    {
        return $this->vendorId;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getGrossMinor(): int
    {
        return $this->grossMinor;
    }

    public function getNetMinor(): int
    {
        return $this->netMinor;
    }
}
