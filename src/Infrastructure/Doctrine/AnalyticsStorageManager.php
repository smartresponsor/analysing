<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

final class AnalyticsStorageManager
{
    public function __construct(
        private readonly ConnectionFactory $connectionFactory,
        private readonly AnalyticsStorageSchemaDefinition $definition,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @return array{
     *   ready: bool,
     *   mode: string,
     *   path: ?string,
     *   required_tables: list<string>,
     *   existing_tables: list<string>,
     *   missing_tables: list<string>
     * }
     */
    public function inspect(): array
    {
        $connection = $this->connectionFactory->create();
        $schemaManager = $connection->createSchemaManager();
        $existingTables = array_values(array_map(
            static fn (string $name): string => strtolower($name),
            $schemaManager->listTableNames(),
        ));
        sort($existingTables);

        $requiredTables = $this->definition->requiredTableNames();
        sort($requiredTables);
        $missingTables = array_values(array_diff($requiredTables, $existingTables));
        sort($missingTables);

        return [
            'ready' => [] === $missingTables,
            'mode' => $this->detectMode($connection),
            'path' => $this->detectPath($connection),
            'required_tables' => $requiredTables,
            'existing_tables' => $existingTables,
            'missing_tables' => $missingTables,
        ];
    }

    /**
     * @return array{
     *   mode: string,
     *   path: ?string,
     *   executed_sql_count: int,
     *   seeded: bool,
     *   created_tables: list<string>,
     *   missing_tables: list<string>
     * }
     */
    public function prepare(bool $seed = false): array
    {
        $connection = $this->connectionFactory->create();
        $schemaManager = $connection->createSchemaManager();
        $currentSchema = $schemaManager->introspectSchema();
        $targetSchema = $this->definition->createSchema();
        $sql = $currentSchema->getMigrateToSql($targetSchema, $connection->getDatabasePlatform());

        $connection->beginTransaction();

        try {
            foreach ($sql as $statement) {
                $connection->executeStatement($statement);
            }

            if ($seed) {
                $this->seedIfEmpty($connection);
            }

            $connection->commit();
        } catch (\Throwable $exception) {
            $connection->rollBack();
            $this->logger->error('Analytics storage preparation failed.', [
                'exception' => $exception,
                'mode' => $this->detectMode($connection),
                'path' => $this->detectPath($connection),
            ]);

            throw new \RuntimeException('Analytics storage preparation failed: '.$exception->getMessage(), 0, $exception);
        }

        $inspection = $this->inspect();

        return [
            'mode' => $inspection['mode'],
            'path' => $inspection['path'],
            'executed_sql_count' => count($sql),
            'seeded' => $seed,
            'created_tables' => array_values(array_intersect($this->definition->requiredTableNames(), $inspection['existing_tables'])),
            'missing_tables' => $inspection['missing_tables'],
        ];
    }

    private function detectMode(Connection $connection): string
    {
        return $connection->getDatabasePlatform()->getName();
    }

    private function detectPath(Connection $connection): ?string
    {
        $params = $connection->getParams();
        $path = $params['path'] ?? null;

        return is_string($path) && '' !== trim($path) ? $path : null;
    }

    private function seedIfEmpty(Connection $connection): void
    {
        if (0 === $this->readCount($connection, 'aggregate_funnel_daily')) {
            $this->seedAggregateTables($connection);
        }

        if (0 === $this->readCount($connection, 'analytics_alert_rule')) {
            $now = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
            $connection->insert('analytics_alert_rule', [
                'code' => 'gross-drop',
                'name' => 'Gross drop alert',
                'condition' => json_encode(['metric' => 'gross_minor', 'operator' => 'lt', 'threshold' => 10000], JSON_THROW_ON_ERROR),
                'channels' => json_encode(['log'], JSON_THROW_ON_ERROR),
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }


    private function readCount(Connection $connection, string $table): int
    {
        $value = $connection->fetchOne(sprintf('SELECT COUNT(*) FROM %s', $table));
        if (!is_scalar($value) && null !== $value) {
            throw new \RuntimeException(sprintf('Analytics storage count query for %s returned a non-scalar result.', $table));
        }

        return (int) $value;
    }

    private function seedAggregateTables(Connection $connection): void
    {
        $today = new \DateTimeImmutable('today', new \DateTimeZone('UTC'));

        for ($i = 6; $i >= 0; --$i) {
            $day = $today->modify(sprintf('-%d days', $i))->format('Y-m-d');
            $gross = 240 - ($i * 12);
            $active = 120 - ($i * 2);
            $transition = 80 - ($i * 3);

            $connection->insert('aggregate_funnel_daily', [
                'day' => $day,
                'app' => 'shop',
                'env' => 'prod',
                'step_1' => 'visit',
                'step_2' => 'checkout',
                'step_3' => 'payment',
                'step_4' => null,
                'user_count' => $gross,
            ]);

            $connection->insert('path_transition_daily', [
                'day' => $day,
                'app' => 'shop',
                'env' => 'prod',
                'from_event' => 'visit',
                'to_event' => 'checkout',
                'transition_count' => $transition,
            ]);
        }

        for ($dayOffset = 0; $dayOffset <= 7; ++$dayOffset) {
            $connection->insert('retention_cohort_daily', [
                'cohort' => $today->modify('-7 days')->format('Y-m-d'),
                'day_offset' => $dayOffset,
                'app' => 'shop',
                'env' => 'prod',
                'active_user' => max(0, 120 - ($dayOffset * 11)),
            ]);
        }

        $connection->insert('experiment_metric_daily', [
            'day' => $today->format('Y-m-d'),
            'experiment_key' => 'exp-home',
            'variant_key' => 'control',
            'exposure' => 100,
            'conversion' => 17,
            'value_sum' => 1425.0,
        ]);
    }
}
