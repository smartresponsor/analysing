<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Entity\Alerts\AnalyticsAlertRuleEntity;
use App\Analysing\Entity\Analytics\AnalyticsDashboardMetricSnapshotEntity;
use App\Analysing\Entity\Analytics\AnalyticsExperimentMetricDailyEntity;
use App\Analysing\Entity\Analytics\AnalyticsExportJobEntity;
use App\Analysing\Entity\Analytics\AnalyticsFunnelDailyEntity;
use App\Analysing\Entity\Analytics\AnalyticsPathTransitionDailyEntity;
use App\Analysing\Entity\Analytics\AnalyticsRetentionCohortDailyEntity;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\TestCase;

final class AnalyticsStorageSchemaDefinitionTest extends TestCase
{
    public function testOrmEntitiesDefineExpectedTables(): void
    {
        $entityManager = $this->createEntityManager([
            AnalyticsDashboardMetricSnapshotEntity::class,
            AnalyticsExportJobEntity::class,
            AnalyticsAlertRuleEntity::class,
            AnalyticsFunnelDailyEntity::class,
            AnalyticsRetentionCohortDailyEntity::class,
            AnalyticsPathTransitionDailyEntity::class,
            AnalyticsExperimentMetricDailyEntity::class,
        ]);

        $schemaTool = new SchemaTool($entityManager);
        $tables = $schemaTool->getCreateSchemaSql(
            array_map(
                static fn (string $entityClass) => $entityManager->getClassMetadata($entityClass),
                [
                    AnalyticsDashboardMetricSnapshotEntity::class,
                    AnalyticsExportJobEntity::class,
                    AnalyticsAlertRuleEntity::class,
                    AnalyticsFunnelDailyEntity::class,
                    AnalyticsRetentionCohortDailyEntity::class,
                    AnalyticsPathTransitionDailyEntity::class,
                    AnalyticsExperimentMetricDailyEntity::class,
                ],
            )
        );

        self::assertNotEmpty($tables);
    }

    /**
     * @param list<class-string> $entityClasses
     */
    private function createEntityManager(array $entityClasses): EntityManager
    {
        $config = ORMSetup::createAttributeMetadataConfig([dirname(__DIR__, 3).'/src/Entity'], true);
        $config->enableNativeLazyObjects(true);
        $connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ], new Configuration());

        $em = new EntityManager($connection, $config);
        $schemaTool = new SchemaTool($em);
        $metadata = [];
        foreach ($entityClasses as $entityClass) {
            $metadata[] = $em->getClassMetadata($entityClass);
        }
        $schemaTool->createSchema($metadata);

        return $em;
    }
}
