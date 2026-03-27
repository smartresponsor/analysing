<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use PHPUnit\Framework\TestCase;

final class CronAndEntryStructureTest extends TestCase
{
    public function testCronAndEntryArtifactsExistAndContainExpectedMarkers(): void
    {
        $cron = (string) file_get_contents(__DIR__.'/../../../config/cron/analytics_tick.cron');
        $publicIndex = (string) file_get_contents(__DIR__.'/../../../public/index.php');
        $dockerfile = (string) file_get_contents(__DIR__.'/../../../docker/Dockerfile');
        $compose = (string) file_get_contents(__DIR__.'/../../../docker/docker-compose.yml');

        self::assertStringContainsString('analytics:tick', $cron);
        self::assertStringContainsString('analytics-oct30-final-winners', $publicIndex);
        self::assertStringContainsString('php', strtolower($dockerfile));
        self::assertStringContainsString('services', strtolower($compose));
    }
}
