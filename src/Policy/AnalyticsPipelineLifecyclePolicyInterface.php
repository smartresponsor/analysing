<?php

declare(strict_types=1);

namespace App\Analysing\Policy;

interface AnalyticsPipelineLifecyclePolicyInterface
{
    public static function canTransition(string $from, string $to): bool;

    public static function assertCanTransition(string $from, string $to): void;

    /** @return list<string> */
    public static function allowedTargets(string $from): array;

    /** @return list<string> */
    public static function knownStates(): array;
}
