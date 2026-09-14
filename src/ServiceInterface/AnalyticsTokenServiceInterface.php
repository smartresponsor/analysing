<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface;

interface AnalyticsTokenServiceInterface
{
    /**
     * @param array<string,mixed> $scope
     *
     * @return non-empty-string
     */
    public function issue(array $scope, int $ttl = 3600): string;

    /** @return array<string,mixed> */
    public function verify(string $token): array;
}
