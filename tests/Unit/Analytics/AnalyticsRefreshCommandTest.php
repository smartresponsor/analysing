<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\Command\AnalyticsRefreshCommand;
use App\ServiceInterface\Analytics\AnalyticsCollectorInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Tester\CommandTester;

final class AnalyticsRefreshCommandTest extends TestCase
{
    public function testExecuteRecordsRefreshSnapshot(): void
    {
        $collector = $this->createMock(AnalyticsCollectorInterface::class);
        $collector->expects(self::once())
            ->method('record')
            ->with(
                'orders',
                0.0,
                self::isInstanceOf(\DateTimeImmutable::class),
                self::isInstanceOf(\DateTimeImmutable::class),
                self::callback(static fn (array $dimensions): bool => ($dimensions['command'] ?? null) === 'analytics:refresh')
            );

        $command = new AnalyticsRefreshCommand($collector, $this->createMock(LoggerInterface::class));
        $tester = new CommandTester($command);

        self::assertSame(0, $tester->execute([]));
        self::assertStringContainsString('Analytics refreshed.', $tester->getDisplay());
    }
}
