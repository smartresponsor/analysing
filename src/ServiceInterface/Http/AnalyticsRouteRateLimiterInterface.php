<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface\Http;

use App\Analysing\ValueObject\Http\AnalyticsRateLimitDecision;

interface AnalyticsRouteRateLimiterInterface
{
    public function isEnabled(): bool;

    public function consume(string $route, string $scope): AnalyticsRateLimitDecision;
}
