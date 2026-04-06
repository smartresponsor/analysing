<?php

declare(strict_types=1);

namespace App\ValueObject\Http;

final readonly class AnalyticsRateLimitDecision
{
    public function __construct(
        public bool $allowed,
        public int $limit,
        public int $remaining,
        public int $retryAfterSeconds,
        public int $resetAt,
        public string $scope,
        public string $mode,
    ) {
    }
}
