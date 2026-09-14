<?php

declare(strict_types=1);

namespace App\Analysing\Service\Doctrine;

use App\Analysing\Entity\Alerts\AnalyticsAlertRuleEntity;
use App\Analysing\Entity\Analytics\AnalyticsDashboardMetricSnapshotEntity;
use App\Analysing\Entity\Analytics\AnalyticsExperimentMetricDailyEntity;
use App\Analysing\Entity\Analytics\AnalyticsExportJobEntity;
use App\Analysing\Entity\Analytics\AnalyticsFunnelDailyEntity;
use App\Analysing\Entity\Analytics\AnalyticsPathTransitionDailyEntity;
use App\Analysing\Entity\Analytics\AnalyticsRetentionCohortDailyEntity;
use App\Analysing\ServiceInterface\Doctrine\AnalyticsStorageManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Psr\Log\LoggerInterface;

final readonly class AnalyticsStorageManager implements AnalyticsStorageManagerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
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
        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $connection = $this->entityManager->getConnection();
        $schemaManager = $connection->createSchemaManager();
        $existingTables = array_map(
            static fn (string $name): string => strtolower($name),
            $schemaManager->listTableNames(),
        );
        sort($existingTables);

        $requiredTables = $this->requiredTableNames();
        sort($requiredTables);
        $missingTables = array_values(array_diff($requiredTables, $existingTables));
        sort($missingTables);

        return [
            'ready' => [] === $missingTables && [] === $schemaTool->getUpdateSchemaSql($metadata),
            'mode' => $this->detectMode(),
            'path' => $this->detectStoragePath(),
            'required_tables' => $requiredTables,
            'existing_tables' => $existingTables,
            'missing_tables' => $missingTables,
        ];
    }

    /**
     * @param bool $seed
     *
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
        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $connection = $this->entityManager->getConnection();
        $schemaManager = $connection->createSchemaManager();
        $existingTables = array_map(
            static fn (string $name): string => strtolower($name),
            $schemaManager->listTableNames(),
        );
        $requiredTables = $this->requiredTableNames();
        $missingTables = array_values(array_diff($requiredTables, $existingTables));

        $executedSqlCount = count($schemaTool->getUpdateSchemaSql($metadata));
        $this->entityManager->beginTransaction();

        try {
            if ([] !== $missingTables) {
                $schemaTool->updateSchema($metadata);
            }

            if ($seed) {
                $this->seedIfEmpty();
            }

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Throwable $exception) {
            $this->entityManager->rollback();
            $this->logger->error('Analytics storage preparation failed.', [
                'exception' => $exception,
                'mode' => $this->detectMode(),
                'path' => $this->detectStoragePath(),
            ]);

            throw new \RuntimeException('Analytics storage preparation failed: '.$exception->getMessage(), 0, $exception);
        }

        $inspection = $this->inspect();

        return [
            'mode' => $inspection['mode'],
            'path' => $inspection['path'],
            'executed_sql_count' => $executedSqlCount,
            'seeded' => $seed,
            'created_tables' => array_values(array_intersect($requiredTables, $inspection['existing_tables'])),
            'missing_tables' => $inspection['missing_tables'],
        ];
    }

    private function detectMode(): string
    {
        $platformClass = $this->entityManager->getConnection()->getDatabasePlatform()::class;
        $lower = strtolower($platformClass);

        if (str_contains($lower, 'sqlite')) {
            return 'sqlite';
        }
        if (str_contains($lower, 'postgres')) {
            return 'postgresql';
        }
        if (str_contains($lower, 'mysql')) {
            return 'mysql';
        }

        return $platformClass;
    }

    private function detectStoragePath(): ?string
    {
        $url = getenv('ANALYTICS_DATABASE_URL');
        if (is_string($url) && '' !== trim($url)) {
            $parsed = parse_url(trim($url));
            $path = $parsed['path'] ?? null;

            return is_string($path) && '' !== trim($path) ? $path : null;
        }

        return dirname(__DIR__, 3).'/var/analytics.sqlite';
    }

    private function seedIfEmpty(): void
    {
        $this->seedAggregateTables();
        $this->seedAlertRules();
        $this->seedExperimentMetrics();
    }

    private function seedAlertRules(): void
    {
        if (0 !== $this->entityManager->getRepository(AnalyticsAlertRuleEntity::class)->count([])) {
            return;
        }

        $this->entityManager->persist(new AnalyticsAlertRuleEntity(
            'gross-drop',
            'Gross drop alert',
            ['metric' => 'gross_minor', 'operator' => 'lt', 'threshold' => 10000],
            ['log'],
        ));
    }

    private function seedAggregateTables(): void
    {
        if (0 === $this->entityManager->getRepository(AnalyticsFunnelDailyEntity::class)->count([])) {
            $today = new \DateTimeImmutable('today', new \DateTimeZone('UTC'));
            for ($i = 6; $i >= 0; --$i) {
                $day = $today->modify(sprintf('-%d days', $i))->format('Y-m-d');
                $gross = 240 - ($i * 12);
                $transition = 80 - ($i * 3);

                $this->entityManager->persist(new AnalyticsFunnelDailyEntity('shop', 'prod', $day, 'visit', 'checkout', 'payment', null, $gross));
                $this->entityManager->persist(new AnalyticsPathTransitionDailyEntity('shop', 'prod', $day, 'visit', 'checkout', $transition));
            }

            for ($dayOffset = 0; $dayOffset <= 7; ++$dayOffset) {
                $this->entityManager->persist(new AnalyticsRetentionCohortDailyEntity(
                    'shop',
                    'prod',
                    $today->modify('-7 days')->format('Y-m-d'),
                    $dayOffset,
                    max(0, 120 - ($dayOffset * 11)),
                ));
            }
        }
    }

    private function seedExperimentMetrics(): void
    {
        if (0 !== $this->entityManager->getRepository(AnalyticsExperimentMetricDailyEntity::class)->count([])) {
            return;
        }

        $today = new \DateTimeImmutable('today', new \DateTimeZone('UTC'));
        $this->entityManager->persist(new AnalyticsExperimentMetricDailyEntity(
            $today->format('Y-m-d'),
            'exp-home',
            'control',
            100,
            17,
            1425.0,
        ));
    }

    /**
     * @return list<string>
     */
    private function requiredTableNames(): array
    {
        $metadata = [
            $this->entityManager->getClassMetadata(AnalyticsDashboardMetricSnapshotEntity::class),
            $this->entityManager->getClassMetadata(AnalyticsExportJobEntity::class),
            $this->entityManager->getClassMetadata(AnalyticsAlertRuleEntity::class),
            $this->entityManager->getClassMetadata(AnalyticsFunnelDailyEntity::class),
            $this->entityManager->getClassMetadata(AnalyticsRetentionCohortDailyEntity::class),
            $this->entityManager->getClassMetadata(AnalyticsPathTransitionDailyEntity::class),
            $this->entityManager->getClassMetadata(AnalyticsExperimentMetricDailyEntity::class),
        ];

        return array_map(
            static fn (\Doctrine\ORM\Mapping\ClassMetadata $metadata): string => $metadata->getTableName(),
            $metadata,
        );
    }
}
