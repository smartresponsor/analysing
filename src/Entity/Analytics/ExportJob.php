<?php

declare(strict_types=1);

namespace App\Entity\Analytics;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'analytics_export_job')]
#[ORM\Index(name: 'idx_export_job_status', columns: ['status'])]
final class ExportJob
{
    public const string STATUS_PENDING = 'pending';
    public const string STATUS_RUNNING = 'running';
    public const string STATUS_DONE = 'done';
    public const string STATUS_FAILED = 'failed';
    public const int MAX_ATTEMPTS = 3;
    public const int MAX_ATTEMPT_OVERFLOW = 32767;

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

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'finished_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $finishedAt = null;

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
        $this->createdAt = new \DateTimeImmutable();
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
        return $this->createdAt;
    }

    public function getFinishedAt(): ?\DateTimeImmutable
    {
        return $this->finishedAt;
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
     * @param array<string, mixed> $payload
     */
    public function mergePayload(array $payload): void
    {
        $currentPayload = $this->payload ?? [];
        $this->payload = array_replace($currentPayload, $this->normalizePayload($payload));
    }

    public function canRetry(): bool
    {
        return $this->attempts < self::MAX_ATTEMPTS && self::STATUS_FAILED === $this->status;
    }

    public function start(): void
    {
        $this->status = self::STATUS_RUNNING;
        $this->error = null;
        $this->finishedAt = null;
    }

    public function done(): void
    {
        $this->status = self::STATUS_DONE;
        $this->finishedAt = new \DateTimeImmutable();
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
        $this->finishedAt = new \DateTimeImmutable();
    }

    public function incAttempts(): void
    {
        if ($this->attempts >= self::MAX_ATTEMPT_OVERFLOW) {
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
            return array_map(function ($nestedValue) {
                return $this->normalizePayloadValue($nestedValue);
            }, $value);
        }

        if (!is_scalar($value) && null !== $value) {
            throw new \InvalidArgumentException('Export job payload values must be scalar, array, or null.');
        }

        return $value;
    }
}
