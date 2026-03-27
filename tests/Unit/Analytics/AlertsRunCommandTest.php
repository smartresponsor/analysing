<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\Command\AlertsRunCommand;
use App\Entity\Alerts\AlertRule;
use App\Entity\Analytics\MetricSnapshot;
use App\ServiceInterface\Alerts\AlertEvaluatorInterface;
use App\ServiceInterface\Alerts\NotificationDispatcherInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Tester\CommandTester;

final class AlertsRunCommandTest extends TestCase
{
    public function testExecuteDispatchesMatchedAlert(): void
    {
        $rule = new AlertRule('high-orders', 'High orders', ['operator' => 'gt', 'threshold' => 10], ['log']);
        $snapshot = new MetricSnapshot('orders', 42.0, new \DateTimeImmutable('-5 minutes'), new \DateTimeImmutable('now'));

        $evaluator = $this->createMock(AlertEvaluatorInterface::class);
        $evaluator->method('evaluate')->willReturn([
            ['matched' => true, 'rule' => $rule, 'snapshot' => $snapshot],
        ]);

        $dispatcher = $this->createMock(NotificationDispatcherInterface::class);
        $dispatcher->expects(self::once())->method('dispatch');

        $command = new AlertsRunCommand($evaluator, $dispatcher, $this->createMock(LoggerInterface::class));
        $tester = new CommandTester($command);

        self::assertSame(0, $tester->execute([]));
        self::assertStringContainsString('matched=1', $tester->getDisplay());
        self::assertStringContainsString('dispatched=1', $tester->getDisplay());
    }
}
