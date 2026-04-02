<?php

declare(strict_types=1);

namespace App\Tests\Application\Analytics;

use App\Tests\Fixtures\Factories\UserFactory;
use PHPUnit\Framework\TestCase;

/**
 * Validates cohort-specific user fixtures used by application, Panther and Playwright layers.
 */
final class CohortFactoryTest extends TestCase
{
    public function testExistingUserFactoryBuildsHistoricalUserPayload(): void
    {
        $user = UserFactory::existingUser();

        self::assertSame('existing', $user['cohort']);
        self::assertTrue($user['has_history']);
        self::assertFalse($user['is_virtual']);
    }

    public function testNewUserFactoryBuildsFreshUserPayload(): void
    {
        $user = UserFactory::newUser();

        self::assertSame('new', $user['cohort']);
        self::assertFalse($user['has_history']);
        self::assertFalse($user['is_virtual']);
    }

    public function testVirtualUserFactoryBuildsSyntheticUserPayload(): void
    {
        $user = UserFactory::virtualUser();

        self::assertSame('virtual', $user['cohort']);
        self::assertTrue($user['is_virtual']);
    }
}
