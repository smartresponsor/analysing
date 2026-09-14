<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Entity\Analytics\AnalyticsExperimentMetricDailyEntity;
use App\Analysing\Entity\Analytics\AnalyticsFunnelDailyEntity;
use App\Analysing\Entity\Analytics\AnalyticsPathTransitionDailyEntity;
use App\Analysing\Entity\Analytics\AnalyticsRetentionCohortDailyEntity;
use App\Analysing\Repository\AnalyticsRepository;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class AnalyticsRepositoryTest extends TestCase
{
    public function testFetchFunnelNormalizesRows(): void
    {
        $em = $this->createEntityManager([AnalyticsFunnelDailyEntity::class]);
        $em->persist(new AnalyticsFunnelDailyEntity('shop', 'prod', '2026-01-01', 'view', 'cart', 'payment', null, 12));
        $em->flush();

        $repository = new AnalyticsRepository($em, new NullLogger());
        $rows = $repository->fetchFunnel('shop', 'prod', ['view', 'cart'], new \DateTimeImmutable('2026-01-01'), new \DateTimeImmutable('2026-01-07'));

        self::assertSame([['day' => '2026-01-01', 'user_count' => 12]], $rows);
    }

    public function testFetchRetentionReturnsOrderedRows(): void
    {
        $em = $this->createEntityManager([AnalyticsRetentionCohortDailyEntity::class]);
        $em->persist(new AnalyticsRetentionCohortDailyEntity('shop', 'prod', '2026-01-01', 0, 12));
        $em->persist(new AnalyticsRetentionCohortDailyEntity('shop', 'prod', '2026-01-01', 1, 8));
        $em->flush();

        $repository = new AnalyticsRepository($em, new NullLogger());
        $rows = $repository->fetchRetention('shop', 'prod', new \DateTimeImmutable('2026-01-01'), 7);

        self::assertSame([
            ['day_offset' => 0, 'active_user' => 12],
            ['day_offset' => 1, 'active_user' => 8],
        ], $rows);
    }

    public function testFetchPathReturnsTopRows(): void
    {
        $em = $this->createEntityManager([AnalyticsPathTransitionDailyEntity::class]);
        $em->persist(new AnalyticsPathTransitionDailyEntity('shop', 'prod', '2026-01-01', 'view', 'cart', 7));
        $em->persist(new AnalyticsPathTransitionDailyEntity('shop', 'prod', '2026-01-01', 'view', 'checkout', 3));
        $em->flush();

        $repository = new AnalyticsRepository($em, new NullLogger());
        $rows = $repository->fetchPath('shop', 'prod', new \DateTimeImmutable('2026-01-01'), 1);

        self::assertSame([
            ['from_event' => 'view', 'to_event' => 'cart', 'transition_count' => 7],
        ], $rows);
    }

    public function testUpsertUpdatesExistingExperimentMetric(): void
    {
        $em = $this->createEntityManager([AnalyticsExperimentMetricDailyEntity::class]);
        $em->persist(new AnalyticsExperimentMetricDailyEntity('2026-01-01', 'exp', 'A', 10, 2, 5.5));
        $em->flush();

        $repository = new AnalyticsRepository($em, new NullLogger());
        $repository->upsertAnalyticsExperimentMetricDailyEntity('exp', 'A', new \DateTimeImmutable('2026-01-01'), 15, 3, 9.5);

        $entity = $em->getRepository(AnalyticsExperimentMetricDailyEntity::class)->findOneBy([
            'day' => '2026-01-01',
            'experimentKey' => 'exp',
            'variantKey' => 'A',
        ]);

        self::assertInstanceOf(AnalyticsExperimentMetricDailyEntity::class, $entity);
        self::assertSame(15, $entity->getExposure());
        self::assertSame(3, $entity->getConversion());
        self::assertSame(9.5, $entity->getValueSum());
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
