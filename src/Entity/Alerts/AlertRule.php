<?php declare(strict_types=1);
namespace App\Entity\Alerts;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'analytics_alert_rule')]
#[ORM\UniqueConstraint(name: 'uniq_alert_rule_code', columns: ['code'])]
#[ORM\Index(name: 'idx_alert_rule_active', columns: ['is_active'])]
class AlertRule
{
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column(type: 'integer')] private ?int $id = null;
    #[ORM\Column(type: 'string', length: 190)] private string $code;
    #[ORM\Column(type: 'string', length: 255)] private string $name;
    #[ORM\Column(type: 'json')] private array $condition = [];
    #[ORM\Column(type: 'json')] private array $channels = [];
    #[ORM\Column(type: 'boolean', options: ['default' => true])] private bool $is_active = true;
    #[ORM\Column(type: 'datetime_immutable')] private \DateTimeImmutable $created_at;
    #[ORM\Column(type: 'datetime_immutable')] private \DateTimeImmutable $updated_at;

    public function __construct(string $code, string $name, array $condition, array $channels = [])
    {
        $this->code = $code; $this->name = $name; $this->condition = $condition; $this->channels = $channels;
        $now = new \DateTimeImmutable('now'); $this->created_at = $now; $this->updated_at = $now;
    }
    public function getId(): ?int { return $this->id; } public function getCode(): string { return $this->code; }
    public function getName(): string { return $this->name; } public function getCondition(): array { return $this->condition; }
    public function getChannels(): array { return $this->channels; } public function isActive(): bool { return $this->is_active; }
    public function setActive(bool $active): void { $this->is_active = $active; $this->touch(); }
    public function setChannels(array $channels): void { $this->channels = $channels; $this->touch(); }
    public function setCondition(array $condition): void { $this->condition = $condition; $this->touch(); }
    private function touch(): void { $this->updated_at = new \DateTimeImmutable('now'); }
}
