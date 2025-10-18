<?php
declare(strict_types=1);

namespace App\Entity\Analytics;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'analytics_export_job')]
class ExportJob
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(length: 10)]
    private string $format; // csv|xlsx

    #[ORM\Column(type: 'json')]
    private array $params; // ['from' => 'YYYY-MM-DD', 'to' => 'YYYY-MM-DD', 'vendor' => null, 'currency' => 'USD']

    #[ORM\Column(length: 12)]
    private string $status = 'pending'; // pending|done|failed

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $filePath = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    public function __construct(string $format, array $params)
    {
        $this->format = $format;
        $this->params = $params;
        $this->createdAt = new DateTimeImmutable();
    }

    public function markDone(string $path): void { $this->status = 'done'; $this->filePath = $path; }
    public function markFailed(): void { $this->status = 'failed'; }
}
