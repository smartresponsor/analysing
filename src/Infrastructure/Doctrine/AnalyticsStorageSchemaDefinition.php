<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine;

use Doctrine\DBAL\Schema\Schema;

final class AnalyticsStorageSchemaDefinition
{
    public function createSchema(): Schema
    {
        $schema = new Schema();

        $this->createMetricSnapshotTable($schema);
        $this->createExportJobTable($schema);
        $this->createAlertRuleTable($schema);
        $this->createAggregateFunnelDailyTable($schema);
        $this->createRetentionCohortDailyTable($schema);
        $this->createPathTransitionDailyTable($schema);
        $this->createExperimentMetricDailyTable($schema);

        return $schema;
    }

    /** @return list<string> */
    public function requiredTableNames(): array
    {
        return [
            'analytics_metric_snapshot',
            'analytics_export_job',
            'analytics_alert_rule',
            'aggregate_funnel_daily',
            'retention_cohort_daily',
            'path_transition_daily',
            'experiment_metric_daily',
        ];
    }

    private function createMetricSnapshotTable(Schema $schema): void
    {
        $table = $schema->createTable('analytics_metric_snapshot');
        $table->addColumn('id', 'integer', ['autoincrement' => true, 'unsigned' => true]);
        $table->addColumn('metric', 'string', ['length' => 128]);
        $table->addColumn('value', 'float');
        $table->addColumn('dimensions', 'json', ['notnull' => false]);
        $table->addColumn('period_start', 'datetime_immutable');
        $table->addColumn('period_end', 'datetime_immutable');
        $table->addColumn('created_at', 'datetime_immutable');
        $table->setPrimaryKey(['id']);
        $table->addIndex(['metric', 'period_start', 'period_end'], 'idx_metric_period');
    }

    private function createExportJobTable(Schema $schema): void
    {
        $table = $schema->createTable('analytics_export_job');
        $table->addColumn('id', 'integer', ['autoincrement' => true, 'unsigned' => true]);
        $table->addColumn('type', 'string', ['length' => 64]);
        $table->addColumn('status', 'string', ['length' => 16]);
        $table->addColumn('payload', 'json', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime_immutable');
        $table->addColumn('finished_at', 'datetime_immutable', ['notnull' => false]);
        $table->addColumn('error', 'text', ['notnull' => false]);
        $table->addColumn('attempts', 'smallint', ['default' => 0, 'unsigned' => true]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['status'], 'idx_export_job_status');
    }

    private function createAlertRuleTable(Schema $schema): void
    {
        $table = $schema->createTable('analytics_alert_rule');
        $table->addColumn('id', 'integer', ['autoincrement' => true, 'unsigned' => true]);
        $table->addColumn('code', 'string', ['length' => 190]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('condition', 'json');
        $table->addColumn('channels', 'json');
        $table->addColumn('is_active', 'boolean', ['default' => true]);
        $table->addColumn('created_at', 'datetime_immutable');
        $table->addColumn('updated_at', 'datetime_immutable');
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['code'], 'uniq_alert_rule_code');
        $table->addIndex(['is_active'], 'idx_alert_rule_active');
    }

    private function createAggregateFunnelDailyTable(Schema $schema): void
    {
        $table = $schema->createTable('aggregate_funnel_daily');
        $table->addColumn('day', 'date_immutable');
        $table->addColumn('app', 'string', ['length' => 64]);
        $table->addColumn('env', 'string', ['length' => 32]);
        $table->addColumn('step_1', 'string', ['length' => 128]);
        $table->addColumn('step_2', 'string', ['length' => 128]);
        $table->addColumn('step_3', 'string', ['length' => 128, 'notnull' => false]);
        $table->addColumn('step_4', 'string', ['length' => 128, 'notnull' => false]);
        $table->addColumn('user_count', 'integer', ['unsigned' => true]);
        $table->setPrimaryKey(['day', 'app', 'env', 'step_1', 'step_2']);
        $table->addIndex(['app', 'env', 'day'], 'idx_funnel_scope_day');
    }

    private function createRetentionCohortDailyTable(Schema $schema): void
    {
        $table = $schema->createTable('retention_cohort_daily');
        $table->addColumn('cohort', 'date_immutable');
        $table->addColumn('day_offset', 'integer', ['unsigned' => true]);
        $table->addColumn('app', 'string', ['length' => 64]);
        $table->addColumn('env', 'string', ['length' => 32]);
        $table->addColumn('active_user', 'integer', ['unsigned' => true]);
        $table->setPrimaryKey(['cohort', 'day_offset', 'app', 'env']);
        $table->addIndex(['app', 'env', 'cohort'], 'idx_retention_scope_cohort');
    }

    private function createPathTransitionDailyTable(Schema $schema): void
    {
        $table = $schema->createTable('path_transition_daily');
        $table->addColumn('day', 'date_immutable');
        $table->addColumn('app', 'string', ['length' => 64]);
        $table->addColumn('env', 'string', ['length' => 32]);
        $table->addColumn('from_event', 'string', ['length' => 128]);
        $table->addColumn('to_event', 'string', ['length' => 128]);
        $table->addColumn('transition_count', 'integer', ['unsigned' => true]);
        $table->setPrimaryKey(['day', 'app', 'env', 'from_event', 'to_event']);
        $table->addIndex(['app', 'env', 'day'], 'idx_path_scope_day');
    }

    private function createExperimentMetricDailyTable(Schema $schema): void
    {
        $table = $schema->createTable('experiment_metric_daily');
        $table->addColumn('day', 'date_immutable');
        $table->addColumn('experiment_key', 'string', ['length' => 128]);
        $table->addColumn('variant_key', 'string', ['length' => 128]);
        $table->addColumn('exposure', 'integer', ['unsigned' => true]);
        $table->addColumn('conversion', 'integer', ['unsigned' => true]);
        $table->addColumn('value_sum', 'float');
        $table->setPrimaryKey(['day', 'experiment_key', 'variant_key']);
        $table->addIndex(['experiment_key', 'variant_key', 'day'], 'idx_experiment_metric_scope_day');
    }
}
