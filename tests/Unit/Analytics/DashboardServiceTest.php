<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\DTO\AnalyticsKpiRequestDTO;
use App\Analysing\Entity\Analytics\AnalyticsDashboardMetricSnapshotEntity;
use App\Analysing\Service\AnalyticsDashboardService;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class DashboardServiceTest extends TestCase
{
    public function testKpiAggregatesSnapshotsViaOrm(): void
    {
        $em = $this->createEntityManager([AnalyticsDashboardMetricSnapshotEntity::class]);
        $em->persist(new AnalyticsDashboardMetricSnapshotEntity(1, 'USD', new \DateTimeImmutable('2026-01-01 00:00:00'), 400, 300));
        $em->persist(new AnalyticsDashboardMetricSnapshotEntity(1, 'USD', new \DateTimeImmutable('2026-01-02 00:00:00'), 500, 350));
        $em->persist(new AnalyticsDashboardMetricSnapshotEntity(1, 'USD', new \DateTimeImmutable('2026-01-03 00:00:00'), 300, 250));
        $em->flush();

        $service = new AnalyticsDashboardService($em, new NullLogger());
        $result = $service->kpi(new AnalyticsKpiRequestDTO(1, 'usd', '2026-01-01 00:00:00', '2026-01-03 23:59:59'));

        self::assertSame(1200, $result['gross_minor']);
        self::assertSame(900, $result['net_minor']);
        self::assertSame(75.0, $result['margin_pct']);
        self::assertSame(3, $result['days']);
    }

    public function testTimeseriesGroupsRowsBySnapshotDate(): void
    {
        $em = $this->createEntityManager([AnalyticsDashboardMetricSnapshotEntity::class]);
        $em->persist(new AnalyticsDashboardMetricSnapshotEntity(1, 'USD', new \DateTimeImmutable('2026-01-01 00:00:00'), 100, 90));
        $em->persist(new AnalyticsDashboardMetricSnapshotEntity(1, 'USD', new \DateTimeImmutable('2026-01-01 00:00:00'), 50, 40));
        $em->flush();

        $service = new AnalyticsDashboardService($em, new NullLogger());
        $rows = $service->timeseries(new AnalyticsKpiRequestDTO(1, 'USD', '2026-01-01 00:00:00', '2026-01-01 23:59:59'));

        self::assertSame([
            ['date' => '2026-01-01', 'gross_minor' => 150, 'net_minor' => 130],
        ], $rows);
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
