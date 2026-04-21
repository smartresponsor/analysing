<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Entity\Alerts\AlertRule;
use App\Analysing\Entity\Analytics\MetricSnapshot;
use App\Analysing\Service\Alerts\AlertEvaluator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class AlertEvaluatorTest extends TestCase
{
    public function testEvaluateMatchesRuleWithSnapshotInRange(): void
    {
        $from = new \DateTimeImmutable('2026-01-01 00:00:00');
        $to = new \DateTimeImmutable('2026-01-31 23:59:59');
        $rule = new AlertRule('sales-high', 'Sales High', [
            'metric' => 'sales',
            'operator' => '>=',
            'value' => 10,
        ]);
        $snapshot = new MetricSnapshot('sales', 15.0, $from, $to);

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findBy')->with(['is_active' => true])->willReturn([$rule]);

        $query = $this->createMock(Query::class);
        $query->method('getOneOrNullResult')->willReturn($snapshot);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('orderBy')->willReturnSelf();
        $qb->method('addOrderBy')->willReturnSelf();
        $qb->method('setMaxResults')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);
        $em->method('createQueryBuilder')->willReturn($qb);

        $service = new AlertEvaluator($em, new NullLogger());
        $result = $service->evaluate($from, $to);

        self::assertCount(1, $result);
        self::assertTrue($result[0]['matched']);
        $entry = $result[0];
        $matchedSnapshot = $entry['snapshot'] ?? null;
        self::assertInstanceOf(MetricSnapshot::class, $matchedSnapshot);
        self::assertSame($snapshot, $matchedSnapshot);
    }

    public function testEvaluateRejectsInvalidRange(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $service = new AlertEvaluator($em, new NullLogger());

        $this->expectException(\InvalidArgumentException::class);
        $service->evaluate(new \DateTimeImmutable('2026-02-01'), new \DateTimeImmutable('2026-01-01'));
    }
}
