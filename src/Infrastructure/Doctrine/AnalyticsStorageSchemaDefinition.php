<?php

declare(strict_types=1);

namespace App\Analysing\Infrastructure\Doctrine;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;

final class AnalyticsStorageSchemaDefinition
{
    /** @return list<string> */
    public function requiredTableNames(): array
    {
        return [
            'aggregate_funnel_daily',
            'retention_cohort_daily',
            'path_transition_daily',
            'experiment_metric_daily',
            'analytics_alert_rule',
        ];
    }

    /**
     * @throws Exception
     * @throws SchemaException
     */
    public function createSchema(): Schema
    {
        $schema = new Schema();

        $funnel = $schema->createTable('aggregate_funnel_daily');
        $funnel->addColumn('day', 'string', ['length' => 10]);
        $funnel->addColumn('app', 'string', ['length' => 64]);
        $funnel->addColumn('env', 'string', ['length' => 64]);
        $funnel->addColumn('step_1', 'string', ['length' => 64]);
        $funnel->addColumn('step_2', 'string', ['length' => 64]);
        $funnel->addColumn('step_3', 'string', ['length' => 64]);
        $funnel->addColumn('step_4', 'string', ['length' => 64, 'notnull' => false]);
        $funnel->addColumn('user_count', 'integer');

        $retention = $schema->createTable('retention_cohort_daily');
        $retention->addColumn('cohort', 'string', ['length' => 10]);
        $retention->addColumn('day_offset', 'integer');
        $retention->addColumn('app', 'string', ['length' => 64]);
        $retention->addColumn('env', 'string', ['length' => 64]);
        $retention->addColumn('active_user', 'integer');

        $path = $schema->createTable('path_transition_daily');
        $path->addColumn('day', 'string', ['length' => 10]);
        $path->addColumn('app', 'string', ['length' => 64]);
        $path->addColumn('env', 'string', ['length' => 64]);
        $path->addColumn('from_event', 'string', ['length' => 64]);
        $path->addColumn('to_event', 'string', ['length' => 64]);
        $path->addColumn('transition_count', 'integer');

        $experiment = $schema->createTable('experiment_metric_daily');
        $experiment->addColumn('day', 'string', ['length' => 10]);
        $experiment->addColumn('experiment_key', 'string', ['length' => 128]);
        $experiment->addColumn('variant_key', 'string', ['length' => 128]);
        $experiment->addColumn('exposure', 'integer');
        $experiment->addColumn('conversion', 'integer');
        $experiment->addColumn('value_sum', 'float');

        $alerts = $schema->createTable('analytics_alert_rule');
        $alerts->addColumn('code', 'string', ['length' => 190]);
        $alerts->addColumn('name', 'string', ['length' => 255]);
        $alerts->addColumn('condition', 'text');
        $alerts->addColumn('channels', 'text');
        $alerts->addColumn('is_active', 'boolean');
        $alerts->addColumn('created_at', 'string', ['length' => 19]);
        $alerts->addColumn('updated_at', 'string', ['length' => 19]);

        return $schema;
    }
}
