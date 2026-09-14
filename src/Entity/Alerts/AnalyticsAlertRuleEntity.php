<?php

declare(strict_types=1);

namespace App\Analysing\Entity\Alerts;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'analytics_alert_rule')]
#[ORM\UniqueConstraint(name: 'uniq_alert_rule_code', columns: ['code'])]
#[ORM\Index(name: 'idx_alert_rule_active', columns: ['is_active'])]
class AnalyticsAlertRuleEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id = 0;

    #[ORM\Column(type: 'string', length: 190)]
    private string $code;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    /** @var array<string,mixed> */
    #[ORM\Column(type: 'json')]
    private array $condition;

    /** @var list<mixed> */
    #[ORM\Column(type: 'json')]
    private array $channels;

    #[ORM\Column(name: 'is_active', type: 'boolean', options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    /**
     * @param array<string,mixed> $condition
     * @param list<mixed>         $channels
     */
    public function __construct(string $code, string $name, array $condition, array $channels = [])
    {
        $this->code = $this->normalizeCode($code);
        $this->name = $this->normalizeName($name);
        $this->condition = $this->normalizeCondition($condition);
        $this->channels = $this->normalizeChannels($channels);
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /** @return array<string,mixed> */
    public function getCondition(): array
    {
        return $this->condition;
    }

    /** @return list<mixed> */
    public function getChannels(): array
    {
        return $this->channels;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setActive(bool $active): void
    {
        $this->isActive = $active;
        $this->touch();
    }

    /** @param list<mixed> $channels */
    public function setChannels(array $channels): void
    {
        $this->channels = $this->normalizeChannels($channels);
        $this->touch();
    }

    /** @param array<string,mixed> $condition */
    public function setCondition(array $condition): void
    {
        $this->condition = $this->normalizeCondition($condition);
        $this->touch();
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    private function normalizeCode(string $code): string
    {
        $normalized = trim($code);
        if ('' === $normalized) {
            throw new \InvalidArgumentException('Alert rule code must not be empty.');
        }

        return $normalized;
    }

    private function normalizeName(string $name): string
    {
        $normalized = trim($name);
        if ('' === $normalized) {
            throw new \InvalidArgumentException('Alert rule name must not be empty.');
        }

        return $normalized;
    }

    /**
     * @param array<string,mixed> $condition
     *
     * @return array<string,mixed>
     */
    private function normalizeCondition(array $condition): array
    {
        if ([] === $condition) {
            throw new \InvalidArgumentException('Alert rule condition must not be empty.');
        }

        $normalized = [];
        foreach ($condition as $key => $value) {
            if (!is_string($key) || '' === trim($key)) {
                throw new \InvalidArgumentException('Alert rule condition keys must be non-empty strings.');
            }

            if (is_array($value)) {
                $normalized[$key] = $this->normalizeNestedArray($value, 'condition');
                continue;
            }

            if (!is_scalar($value) && null !== $value) {
                throw new \InvalidArgumentException('Alert rule condition values must be scalar, array, or null.');
            }

            $normalized[$key] = $value;
        }

        return $normalized;
    }

    /**
     * @param list<mixed> $channels
     *
     * @return list<mixed>
     */
    private function normalizeChannels(array $channels): array
    {
        $normalized = [];
        foreach ($channels as $index => $channel) {
            if (is_string($channel)) {
                $value = trim($channel);
                if ('' === $value) {
                    throw new \InvalidArgumentException(sprintf('Alert rule channel at index %d must not be empty.', $index));
                }

                $normalized[] = $value;
                continue;
            }

            if (is_array($channel)) {
                $normalized[] = $this->normalizeNestedArray($channel, 'channel');
                continue;
            }

            throw new \InvalidArgumentException(sprintf('Alert rule channel at index %d must be a string or array.', $index));
        }

        return $normalized;
    }

    /**
     * @param array<array-key, mixed> $values
     *
     * @return array<array-key, mixed>
     */
    private function normalizeNestedArray(array $values, string $context): array
    {
        /** @var array<array-key, mixed> $normalized */
        $normalized = [];
        foreach ($values as $key => $value) {
            if (is_array($value)) {
                $normalized[$key] = $this->normalizeNestedArray($value, $context);
                continue;
            }

            if (!is_scalar($value) && null !== $value) {
                throw new \InvalidArgumentException(sprintf('Alert rule %s values must be scalar, array, or null.', $context));
            }

            $normalized[$key] = $value;
        }

        return $normalized;
    }
}
