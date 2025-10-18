<?php
declare(strict_types=1);

namespace App\Entity\Alerts;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'alert_log')]
class AlertLog
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'integer')]
    private int $vendorId;

    #[ORM\Column(length: 32)]
    private string $type;

    #[ORM\Column(type: 'text')]
    private string $message;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $context;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    public function __construct(int $vendorId, string $type, string $message, array $context = [])
    {
        $this->vendorId = $vendorId;
        $this->type = $type;
        $this->message = $message;
        $this->context = $context;
        $this->createdAt = new DateTimeImmutable();
    }
}
