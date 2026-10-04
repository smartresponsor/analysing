<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Entity\Alerts\AnalyticsAlertRuleEntity;
use App\Analysing\Entity\Analytics\AnalyticsMetricSnapshotEntity;
use App\Analysing\RepositoryInterface\AnalyticsMetricSnapshotRepositoryInterface;
use App\Analysing\Service\Alerts\AnalyticsAlertEvaluator;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class AlertEvaluatorTest extends TestCase
{
    public function testEvaluateMatchesRuleWithSnapshotInRange(): void
    {
        $from = new \DateTimeImmutable('2026-01-01 00:00:00');
        $to = new \DateTimeImmutable('2026-01-31 23:59:59');
        $rule = new AnalyticsAlertRuleEntity('sales-high', 'Sales High', [
            'metric' => 'sales',
            'operator' => '>=',
            'value' => 10,
        ]);
        $snapshot = new AnalyticsMetricSnapshotEntity('sales', 15.0, $from, $to);

        $snapshots = $this->createMock(AnalyticsMetricSnapshotRepositoryInterface::class);
        $snapshots->method('findActiveAlertRules')->willReturn([$rule]);
        $snapshots->method('findLatestInRange')->with('sales', $from, $to)->willReturn($snapshot);

        $service = new AnalyticsAlertEvaluator($snapshots, new NullLogger());
        $result = $service->evaluate($from, $to);

        self::assertCount(1, $result);
        self::assertTrue($result[0]['matched']);
        $entry = $result[0];
        $matchedSnapshot = $entry['snapshot'] ?? null;
        self::assertInstanceOf(AnalyticsMetricSnapshotEntity::class, $matchedSnapshot);
        self::assertSame($snapshot, $matchedSnapshot);
    }

    public function testEvaluateRejectsInvalidRange(): void
    {
        $snapshots = $this->createMock(AnalyticsMetricSnapshotRepositoryInterface::class);
        $service = new AnalyticsAlertEvaluator($snapshots, new NullLogger());

        $this->expectException(\InvalidArgumentException::class);
        $service->evaluate(new \DateTimeImmutable('2026-02-01'), new \DateTimeImmutable('2026-01-01'));
    }
}
