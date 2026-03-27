<?php

declare(strict_types=1);

namespace App\ValueObject\Analytics;

final class TenantId
{
    private string $value;

    public function __construct(string $value)
    {
        $normalized = trim($value);
        if ('' === $normalized) {
            throw new \InvalidArgumentException('Analytics tenant id must not be empty.');
        }

        $this->value = $normalized;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
