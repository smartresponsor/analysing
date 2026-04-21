<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Infrastructure\Doctrine\AnalyticsStorageSchemaDefinition;
use Doctrine\DBAL\Schema\Table;
use PHPUnit\Framework\TestCase;

final class AnalyticsStorageSchemaDefinitionTest extends TestCase
{
    public function testRequiredTablesArePresentInSchemaDefinition(): void
    {
        $definition = new AnalyticsStorageSchemaDefinition();
        $schema = $definition->createSchema();
        $tableNames = array_map(
            static fn (Table $table): string => str_contains($table->getName(), '.')
                ? substr($table->getName(), (int) strrpos($table->getName(), '.') + 1)
                : $table->getName(),
            $schema->getTables()
        );
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
