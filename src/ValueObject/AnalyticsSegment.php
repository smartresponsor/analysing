<?php

declare(strict_types=1);

namespace App\Analysing\ValueObject;

final class AnalyticsSegment
{
    private string $code;

    public function __construct(string $code)
    {
        $normalized = trim($code);
        if ('' === $normalized) {
            throw new \InvalidArgumentException('Analytics segment code must not be empty.');
        }

        $this->code = $normalized;
    }

    public function code(): string
    {
        return $this->code;
    }

    public function __toString(): string
    {
        return $this->code;
    }
}
