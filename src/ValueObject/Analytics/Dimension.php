<?php

declare(strict_types=1);

namespace App\Analysing\ValueObject\Analytics;

final class Dimension
{
    private string $name;

    public function __construct(string $name)
    {
        $normalized = trim($name);
        if ('' === $normalized) {
            throw new \InvalidArgumentException('Analytics dimension name must not be empty.');
        }

        $this->name = $normalized;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
