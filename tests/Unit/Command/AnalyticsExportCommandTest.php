<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command;

use App\Command\AnalyticsExportCommand;
use App\Service\Analytics\ReportRowBuilder;
use App\ServiceInterface\Analytics\DashboardServiceInterface;
use App\ServiceInterface\Analytics\ReportExporterServiceInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Tester\CommandTester;

final class AnalyticsExportCommandTest extends TestCase
{
    public function testCommandUsesExportToPath(): void
    {
        $dashboard = $this->createMock(DashboardServiceInterface::class);
        $dashboard->method('kpi')->willReturn(['gross_minor' => 0, 'net_minor' => 0, 'days' => 0]);
        $dashboard->method('timeseries')->willReturn([]);

        $exporter = $this->createMock(ReportExporterServiceInterface::class);
        $exporter
            ->expects(self::once())
            ->method('exportToPath')
            ->with(self::isType('array'), self::isType('string'))
            ->willReturn('/tmp/test.csv');

        $command = new AnalyticsExportCommand(
            $dashboard,
            $exporter,
            new ReportRowBuilder(),
            new NullLogger()
        );

        $tester = new CommandTester($command);
        $tester->execute(['path' => '/tmp/test.csv']);

        self::assertSame(0, $tester->getStatusCode());
    }
}
