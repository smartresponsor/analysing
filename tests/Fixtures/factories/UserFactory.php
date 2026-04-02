<?php

declare(strict_types=1);

namespace App\Tests\Fixtures\Factories;

/**
 * Builds deterministic user-like payloads for cohort-based tests.
 *
 * The current project does not yet expose a dedicated persisted user entity in the analytics test
 * stack, so this factory returns normalized associative arrays that can be consumed by application,
 * Panther and Playwright test layers.
 */
final class UserFactory
{
    /**
     * Builds a fixture payload representing an existing user with historical state.
     *
     * @param array<string,mixed> $overrides Optional field overrides.
     *
     * @return array<string,mixed>
     */
    public static function existingUser(array $overrides = []): array
    {
        return array_replace([
            'id' => 'existing-user-1',
            'email' => 'existing.user@example.test',
            'cohort' => 'existing',
            'display_name' => 'Existing User',
            'has_history' => true,
            'is_virtual' => false,
        ], $overrides);
    }

    /**
     * Builds a fixture payload representing a new user with little or no history.
     *
     * @param array<string,mixed> $overrides Optional field overrides.
     *
     * @return array<string,mixed>
     */
    public static function newUser(array $overrides = []): array
    {
        return array_replace([
            'id' => 'new-user-1',
            'email' => 'new.user@example.test',
            'cohort' => 'new',
            'display_name' => 'New User',
            'has_history' => false,
            'is_virtual' => false,
        ], $overrides);
    }

    /**
     * Builds a fixture payload representing a synthetic virtual user for canary traffic.
     *
     * @param array<string,mixed> $overrides Optional field overrides.
     *
     * @return array<string,mixed>
     */
    public static function virtualUser(array $overrides = []): array
    {
        return array_replace([
            'id' => 'virtual-user-1',
            'email' => 'virtual.user@example.test',
            'cohort' => 'virtual',
            'display_name' => 'Virtual User',
            'has_history' => false,
            'is_virtual' => true,
        ], $overrides);
    }
}
