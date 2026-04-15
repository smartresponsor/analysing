<?php

declare(strict_types=1);

namespace App\Entity\Alerts;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'alert_log')]
class AlertLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'integer')]
    private int $vendorId;

    #[ORM\Column(length: 32)]
    private string $type;

    #[ORM\Column(type: 'text')]
    private string $message;

    /** @var array<string,mixed>|null */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $context = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    /** @param array<string,mixed> $context */
    public function __construct(int $vendorId, string $type, string $message, array $context = [])
    {
        if ($vendorId <= 0) {
            throw new \InvalidArgumentException('Alert log vendorId must be a positive integer.');
        }

        $normalizedType = trim($type);
        if ('' === $normalizedType) {
            throw new \InvalidArgumentException('Alert log type must not be empty.');
        }

        $normalizedMessage = trim($message);
        if ('' === $normalizedMessage) {
            throw new \InvalidArgumentException('Alert log message must not be empty.');
        }

        $this->vendorId = $vendorId;
        $this->type = $normalizedType;
        $this->message = $normalizedMessage;
        $this->context = $this->normalizeContext($context);
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVendorId(): int
    {
        return $this->vendorId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    /** @return array<string,mixed>|null */
    public function getContext(): ?array
    {
        return $this->context;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @param array<string,mixed> $context
     *
     * @return array<string,mixed>
     */
    private function normalizeContext(array $context): array
    {
        $normalized = [];
        foreach ($context as $key => $value) {
            if (!is_string($key) || '' === trim($key)) {
                throw new \InvalidArgumentException('Alert log context keys must be non-empty strings.');
            }

            if (is_array($value)) {
                $normalized[$key] = $this->normalizeNestedArray($value);
                continue;
            }

            if (!is_scalar($value) && null !== $value) {
                throw new \InvalidArgumentException('Alert log context values must be scalar, array, or null.');
            }

            $normalized[$key] = $value;
        }

        return $normalized;
    }

    /** @param array<string|int,mixed> $values
     * @return array<string|int,mixed>
     */
    private function normalizeNestedArray(array $values): array
    {
        $normalized = [];
        foreach ($values as $key => $value) {
            if (is_array($value)) {
                $normalized[$key] = $this->normalizeNestedArray($value);
                continue;
            }

            if (!is_scalar($value) && null !== $value) {
                throw new \InvalidArgumentException('Alert log nested context values must be scalar, array, or null.');
            }

            $normalized[$key] = $value;
        }

        return $normalized;
    }
}
