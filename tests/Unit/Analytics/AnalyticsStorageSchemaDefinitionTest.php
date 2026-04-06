<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\Infrastructure\Doctrine\AnalyticsStorageSchemaDefinition;
use PHPUnit\Framework\TestCase;

final class AnalyticsStorageSchemaDefinitionTest extends TestCase
{
    public function testRequiredTablesArePresentInSchemaDefinition(): void
    {
        $definition = new AnalyticsStorageSchemaDefinition();
        $schema = $definition->createSchema();
        $tableNames = $schema->getTableNames();
        sort($tableNames);

        $required = $definition->requiredTableNames();
        sort($required);

        self::assertSame($required, $tableNames);
        self::assertContains('aggregate_funnel_daily', $tableNames);
        self::assertContains('retention_cohort_daily', $tableNames);
        self::assertContains('path_transition_daily', $tableNames);
        self::assertContains('experiment_metric_daily', $tableNames);
    }
}
