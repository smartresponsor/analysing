<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use PHPUnit\Framework\TestCase;

final class MigrationPlatformCompatibilityTest extends TestCase
{
    public function testInitialMigrationRendersPostgreSqlWithoutSqliteOnlyTokens(): void
    {
        require_once __DIR__.'/../../../migrations/Version20260914083153.php';

        $method = new \ReflectionMethod(\App\Analysing\Migrations\Version20260914083153::class, 'definition');
        $schema = $method->invoke(null);

        self::assertInstanceOf(Schema::class, $schema);

        $sql = implode("\n", $schema->toSql(new PostgreSQLPlatform()));

        self::assertStringContainsString('CREATE TABLE analytics_export_job', $sql);
        self::assertStringContainsString('CREATE TABLE analytics_alert_rule', $sql);
        self::assertStringNotContainsString('AUTOINCREMENT', strtoupper($sql));
        self::assertStringNotContainsString(' CLOB', strtoupper($sql));
    }
}
