<?php

declare(strict_types=1);

namespace App\Analysing\Policy;

/**
 * Guards allowed lifecycle transitions for Analysing pipeline/read-model records.
 *
 * This is intentionally string-based: it hardens existing persisted statuses
 * without introducing enum-backed schema changes in this pass.
 */
final class AnalyticsPipelineLifecyclePolicy implements AnalyticsPipelineLifecyclePolicyInterface
{
    /** @var array<string, list<string>> */
    private const TRANSITIONS = [
        'scheduled' => ['running', 'cancelled'],
        'running' => ['materialized', 'failed'],
        'failed' => ['scheduled', 'abandoned'],
        'materialized' => ['superseded'],
        'superseded' => [],
        'cancelled' => [],
        'abandoned' => [],
    ];

    public static function canTransition(string $from, string $to): bool
    {
        if ($from === $to) {
            return true;
        }

        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    public static function assertCanTransition(string $from, string $to): void
    {
        if (!self::canTransition($from, $to)) {
            throw new \DomainException(sprintf('Invalid Analysing pipeline/read-model lifecycle transition from "%s" to "%s".', $from, $to));
        }
    }

    /** @return list<string> */
    public static function allowedTargets(string $from): array
    {
        return self::TRANSITIONS[$from] ?? [];
    }

    /** @return list<string> */
    public static function knownStates(): array
    {
        return array_keys(self::TRANSITIONS);
    }
}
