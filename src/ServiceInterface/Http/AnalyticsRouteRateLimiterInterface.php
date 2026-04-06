<?php

declare(strict_types=1);

namespace App\ServiceInterface\Http;

use App\ValueObject\Http\AnalyticsRateLimitDecision;

interface AnalyticsRouteRateLimiterInterface
{
    public function isEnabled(): bool;

    public function consume(string $route, string $scope): AnalyticsRateLimitDecision;
}
