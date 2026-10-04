<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Command\AnalyticsAlertsRunCommand;
use App\Analysing\Entity\Alerts\AnalyticsAlertRuleEntity;
use App\Analysing\Entity\Analytics\AnalyticsMetricSnapshotEntity;
use App\Analysing\ServiceInterface\Alerts\AnalyticsAlertEvaluatorInterface;
use App\Analysing\ServiceInterface\Alerts\AnalyticsNotificationDispatcherInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Tester\CommandTester;

final class AlertsRunCommandTest extends TestCase
{
    public function testExecuteDispatchesMatchedAlert(): void
    {
        $rule = new AnalyticsAlertRuleEntity('high-orders', 'High orders', ['operator' => 'gt', 'threshold' => 10], ['log']);
        $snapshot = new AnalyticsMetricSnapshotEntity('orders', 42.0, new \DateTimeImmutable('-5 minutes'), new \DateTimeImmutable('now'));

        $evaluator = $this->createMock(AnalyticsAlertEvaluatorInterface::class);
        $evaluator->method('evaluate')->willReturn([
            ['matched' => true, 'rule' => $rule, 'snapshot' => $snapshot],
        ]);

        $dispatcher = $this->createMock(AnalyticsNotificationDispatcherInterface::class);
        $dispatcher->expects(self::once())->method('dispatch');

        $command = new AnalyticsAlertsRunCommand($evaluator, $dispatcher, $this->createMock(LoggerInterface::class));
        $tester = new CommandTester($command);

        self::assertSame(0, $tester->execute([]));
        self::assertStringContainsString('matched=1', $tester->getDisplay());
        self::assertStringContainsString('dispatched=1', $tester->getDisplay());
    }

    public function testExecuteSkipsMalformedResultsAndReportsDispatchFailure(): void
    {
        $rule = new AnalyticsAlertRuleEntity('high-orders', 'High orders', ['operator' => 'gt', 'threshold' => 10], ['log']);
        $snapshot = new AnalyticsMetricSnapshotEntity('orders', 42.0, new \DateTimeImmutable('-5 minutes'), new \DateTimeImmutable('now'));

        $evaluator = $this->createMock(AnalyticsAlertEvaluatorInterface::class);
        $evaluator->method('evaluate')->willReturn([
            'malformed',
            ['matched' => false, 'rule' => $rule, 'snapshot' => $snapshot],
            ['matched' => true, 'rule' => 'invalid', 'snapshot' => $snapshot],
            ['matched' => true, 'rule' => $rule, 'snapshot' => $snapshot],
        ]);

        $dispatcher = $this->createMock(AnalyticsNotificationDispatcherInterface::class);
        $dispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->willThrowException(new \RuntimeException('dispatch failed'));

        $command = new AnalyticsAlertsRunCommand($evaluator, $dispatcher, $this->createMock(LoggerInterface::class));
        $tester = new CommandTester($command);

        self::assertSame(1, $tester->execute([]));
        self::assertStringContainsString('matched=1', $tester->getDisplay());
        self::assertStringContainsString('dispatched=0', $tester->getDisplay());
        self::assertStringContainsString('failed=1', $tester->getDisplay());
        self::assertStringContainsString('skipped=2', $tester->getDisplay());
    }
}
