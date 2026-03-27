<?php

declare(strict_types=1);

namespace App\ServiceInterface\Analytics;

interface TokenServiceInterface
{
    /**
     * @param array<string,mixed> $scope
     *
     * @return non-empty-string
     */
    public function issue(array $scope, int $ttl = 3600): string;

    /**
     * @return array{scope?:array<string,mixed>, iat?:int, exp?:int}
     */
    public function verify(string $token): array;
}
