<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\Entity\Alerts\AlertRule;
use App\Entity\Analytics\MetricSnapshot;
use App\Service\Alerts\AlertEvaluator;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
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

        $query = $this->createMock(AbstractQuery::class);
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
        self::assertSame($snapshot, $result[0]['snapshot']);
    }

    public function testEvaluateRejectsInvalidRange(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $service = new AlertEvaluator($em, new NullLogger());

        $this->expectException(\InvalidArgumentException::class);
        $service->evaluate(new \DateTimeImmutable('2026-02-01'), new \DateTimeImmutable('2026-01-01'));
    }
}
