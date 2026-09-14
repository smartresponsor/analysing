<?php

declare(strict_types=1);

namespace App\Analysing\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260914083153 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the initial Analysing relational schema for analytics data.';
    }

    public function up(Schema $schema): void
    {
        foreach (self::definition()->toSql($this->connection->getDatabasePlatform()) as $sql) {
            $this->addSql($sql);
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::definition()->toDropSql($this->connection->getDatabasePlatform()) as $sql) {
            $this->addSql($sql);
        }
    }

    private static function definition(): Schema
    {
        $schema = new Schema();

        $funnel = $schema->createTable('aggregate_funnel_daily');
        self::addGeneratedId($funnel);
        $funnel->addColumn('day', 'string', ['length' => 10]);
        $funnel->addColumn('app', 'string', ['length' => 64]);
        $funnel->addColumn('env', 'string', ['length' => 64]);
        $funnel->addColumn('step_1', 'string', ['length' => 64]);
        $funnel->addColumn('step_2', 'string', ['length' => 64]);
        $funnel->addColumn('step_3', 'string', ['length' => 64]);
        $funnel->addColumn('step_4', 'string', ['length' => 64, 'notnull' => false]);
        $funnel->addColumn('user_count', 'integer');
        $funnel->addIndex(['app', 'env', 'day'], 'idx_funnel_daily_lookup');

        $alertLog = $schema->createTable('alert_log');
        self::addGeneratedId($alertLog);
        $alertLog->addColumn('vendorId', 'integer');
        $alertLog->addColumn('type', 'string', ['length' => 32]);
        $alertLog->addColumn('message', 'text');
        $alertLog->addColumn('context', 'json', ['notnull' => false]);
        $alertLog->addColumn('createdAt', 'datetime_immutable');

        $alertRule = $schema->createTable('analytics_alert_rule');
        self::addGeneratedId($alertRule);
        $alertRule->addColumn('code', 'string', ['length' => 190]);
        $alertRule->addColumn('name', 'string', ['length' => 255]);
        $alertRule->addColumn('condition', 'json');
        $alertRule->addColumn('channels', 'json');
        $alertRule->addColumn('is_active', 'boolean', ['default' => true]);
        $alertRule->addColumn('created_at', 'datetime_immutable');
        $alertRule->addColumn('updated_at', 'datetime_immutable');
        $alertRule->addIndex(['is_active'], 'idx_alert_rule_active');
        $alertRule->addUniqueIndex(['code'], 'uniq_alert_rule_code');

        $exportJob = $schema->createTable('analytics_export_job');
        self::addGeneratedId($exportJob);
        $exportJob->addColumn('type', 'string', ['length' => 64]);
        $exportJob->addColumn('status', 'string', ['length' => 16]);
        $exportJob->addColumn('payload', 'json', ['notnull' => false]);
        $exportJob->addColumn('created_at', 'datetime_immutable');
        $exportJob->addColumn('finished_at', 'datetime_immutable', ['notnull' => false]);
        $exportJob->addColumn('error', 'text', ['notnull' => false]);
        $exportJob->addColumn('attempts', 'smallint', ['unsigned' => true, 'default' => 0]);
        $exportJob->addIndex(['status'], 'idx_export_job_status');

        $metric = $schema->createTable('analytics_metric_snapshot');
        self::addGeneratedId($metric);
        $metric->addColumn('metric', 'string', ['length' => 128]);
        $metric->addColumn('value', 'float');
        $metric->addColumn('dimensions', 'json', ['notnull' => false]);
        $metric->addColumn('period_start', 'datetime_immutable');
        $metric->addColumn('period_end', 'datetime_immutable');
        $metric->addColumn('created_at', 'datetime_immutable');
        $metric->addIndex(['metric', 'period_start', 'period_end'], 'idx_metric_period');

        $experiment = $schema->createTable('experiment_metric_daily');
        self::addGeneratedId($experiment);
        $experiment->addColumn('day', 'string', ['length' => 10]);
        $experiment->addColumn('experiment_key', 'string', ['length' => 128]);
        $experiment->addColumn('variant_key', 'string', ['length' => 128]);
        $experiment->addColumn('exposure', 'integer');
        $experiment->addColumn('conversion', 'integer');
        $experiment->addColumn('value_sum', 'float');
        $experiment->addIndex(['day', 'experiment_key'], 'idx_experiment_metric_daily_lookup');
        $experiment->addUniqueIndex(['day', 'experiment_key', 'variant_key'], 'uniq_experiment_metric_daily_variant');

        $dashboard = $schema->createTable('metric_snapshot');
        self::addGeneratedId($dashboard);
        $dashboard->addColumn('date', 'datetime_immutable');
        $dashboard->addColumn('vendor_id', 'integer');
        $dashboard->addColumn('currency', 'string', ['length' => 3]);
        $dashboard->addColumn('gross_minor', 'integer');
        $dashboard->addColumn('net_minor', 'integer');
        $dashboard->addIndex(['vendor_id', 'currency', 'date'], 'idx_metric_snapshot_vendor_currency_date');

        $path = $schema->createTable('path_transition_daily');
        self::addGeneratedId($path);
        $path->addColumn('day', 'string', ['length' => 10]);
        $path->addColumn('app', 'string', ['length' => 64]);
        $path->addColumn('env', 'string', ['length' => 64]);
        $path->addColumn('from_event', 'string', ['length' => 64]);
        $path->addColumn('to_event', 'string', ['length' => 64]);
        $path->addColumn('transition_count', 'integer');
        $path->addIndex(['app', 'env', 'day', 'transition_count'], 'idx_path_daily_lookup');

        $retention = $schema->createTable('retention_cohort_daily');
        self::addGeneratedId($retention);
        $retention->addColumn('cohort', 'string', ['length' => 10]);
        $retention->addColumn('day_offset', 'integer');
        $retention->addColumn('app', 'string', ['length' => 64]);
        $retention->addColumn('env', 'string', ['length' => 64]);
        $retention->addColumn('active_user', 'integer');
        $retention->addIndex(['app', 'env', 'cohort', 'day_offset'], 'idx_retention_daily_lookup');

        return $schema;
    }

    private static function addGeneratedId(\Doctrine\DBAL\Schema\Table $table): void
    {
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->setPrimaryKey(['id']);
    }
}
