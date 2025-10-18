<?php declare(strict_types=1);
namespace App\Entity\Analytics;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'analytics_export_job')]
#[ORM\Index(name: 'idx_export_job_status', columns: ['status'])]
class ExportJob
{
    public const STATUS_PENDING='pending'; public const STATUS_RUNNING='running';
    public const STATUS_DONE='done'; public const STATUS_FAILED='failed';

    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column(type: 'integer')] private ?int $id = null;
    #[ORM\Column(type: 'string', length: 64)] private string $type;
    #[ORM\Column(type: 'string', length: 16)] private string $status = self::STATUS_PENDING;
    #[ORM\Column(type: 'json', nullable: true)] private ?array $payload = null;
    #[ORM\Column(type: 'datetime_immutable')] private \DateTimeImmutable $created_at;
    #[ORM\Column(type: 'datetime_immutable', nullable: true)] private ?\DateTimeImmutable $finished_at = null;
    #[ORM\Column(type: 'text', nullable: true)] private ?string $error = null;
    #[ORM\Column(type: 'smallint', options: ['unsigned' => true, 'default' => 0])] private int $attempts = 0;

    public function __construct(string $type, ?array $payload = null)
    { $this->type=$type; $this->payload=$payload; $this->created_at=new \DateTimeImmutable('now'); }

    public function getId(): ?int { return $this->id; } public function getType(): string { return $this->type; }
    public function getStatus(): string { return $this->status; } public function getPayload(): ?array { return $this->payload; }
    public function getError(): ?string { return $this->error; } public function getAttempts(): int { return $this->attempts; }
    public function start(): void { $this->status=self::STATUS_RUNNING; }
    public function done(): void { $this->status=self::STATUS_DONE; $this->finished_at=new \DateTimeImmutable('now'); }
    public function fail(string $message): void { $this->status=self::STATUS_FAILED; $this->error=$message; $this->finished_at=new \DateTimeImmutable('now'); }
    public function incAttempts(): void { $this->attempts++; }
}
