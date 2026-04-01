<?php

declare(strict_types=1);

namespace App\Entity\Analytics;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'analytics_export_job')]
#[ORM\Index(name: 'idx_export_job_status', columns: ['status'])]
final class ExportJob
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_DONE = 'done';
    public const STATUS_FAILED = 'failed';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 64)]
    private string $type;

    #[ORM\Column(type: 'string', length: 16)]
    private string $status = self::STATUS_PENDING;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $payload = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $created_at;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $finished_at = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $error = null;

    #[ORM\Column(type: 'smallint', options: ['unsigned' => true, 'default' => 0])]
    private int $attempts = 0;

    /**
     * @param array<string, mixed>|null $payload
     */
    public function __construct(string $type, ?array $payload = null)
    {
        $normalizedType = strtolower(trim($type));
        if ('' === $normalizedType) {
            throw new \InvalidArgumentException('Export job type must not be empty.');
        }

        $this->type = $normalizedType;
        $this->payload = null === $payload ? null : $this->normalizePayload($payload);
        $this->created_at = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getPayload(): ?array
    {
        return $this->payload;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->created_at;
    }

    public function getFinishedAt(): ?\DateTimeImmutable
    {
        return $this->finished_at;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function getAttempts(): int
    {
        return $this->attempts;
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function mergePayload(array $payload): void
    {
        $normalized = $this->normalizePayload($payload);
        $current = $this->payload ?? [];
        $this->payload = array_replace($current, $normalized);
    }

    public function start(): void
    {
        $this->status = self::STATUS_RUNNING;
        $this->error = null;
        $this->finished_at = null;
    }

    public function done(): void
    {
        $this->status = self::STATUS_DONE;
        $this->finished_at = new \DateTimeImmutable();
        $this->error = null;
    }

    public function fail(string $message): void
    {
        $normalized = trim($message);
        if ('' === $normalized) {
            $normalized = 'Unknown export job failure.';
        }

        $this->status = self::STATUS_FAILED;
        $this->error = $normalized;
        $this->finished_at = new \DateTimeImmutable();
    }

    public function incAttempts(): void
    {
        if ($this->attempts >= 32767) {
            throw new \InvalidArgumentException('Export job attempts overflow.');
        }

        ++$this->attempts;
    }

    /**
     * @param array<string,mixed> $payload
     *
     * @return array<string,mixed>
     */
    private function normalizePayload(array $payload): array
    {
        $normalized = [];
        foreach ($payload as $key => $value) {
            if (!is_string($key) || '' === trim($key)) {
                throw new \InvalidArgumentException('Export job payload keys must be non-empty strings.');
            }

            $normalized[trim($key)] = $this->normalizePayloadValue($value);
        }

        return $normalized;
    }

    private function normalizePayloadValue(mixed $value): mixed
    {
        if (is_array($value)) {
            $normalized = [];
            foreach ($value as $key => $nestedValue) {
                $normalized[$key] = $this->normalizePayloadValue($nestedValue);
            }

            return $normalized;
        }

        if (!is_scalar($value) && null !== $value) {
            throw new \InvalidArgumentException('Export job payload values must be scalar, array, or null.');
        }

        return $value;
    }
}
