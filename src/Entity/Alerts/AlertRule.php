<?php
declare(strict_types=1);

namespace App\Entity\Alerts;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'alert_rule')]
class AlertRule
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(length: 32)]
    private string $type; // negative_margin | high_risk | limit_breach

    #[ORM\Column(type: 'decimal', precision: 8, scale: 2)]
    private string $threshold;

    #[ORM\Column(type: 'boolean')]
    private bool $active = true;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $message = null;

    public function __construct(string $type, string $threshold, ?string $message = null)
    {
        $this->type = $type;
        $this->threshold = $threshold;
        $this->message = $message;
    }

    public function isActive(): bool { return $this->active; }
    public function getType(): string { return $this->type; }
    public function getThreshold(): float { return (float)$this->threshold; }
    public function getMessage(): ?string { return $this->message; }
}
