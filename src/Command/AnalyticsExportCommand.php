<?php

declare(strict_types=1);

namespace App\Command;

use App\DTO\Analytics\KpiRequest;
use App\Service\Analytics\ReportRowBuilder;
use App\ServiceInterface\Analytics\DashboardServiceInterface;
use App\ServiceInterface\Analytics\ReportExporterServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'analytics:export:csv', description: 'Export KPI aggregates to CSV')]
final class AnalyticsExportCommand extends BaseCommand
{
    private const MAX_TARGET_PATH_LENGTH = 4096;

    public function __construct(
        private readonly DashboardServiceInterface $dashboard,
        private readonly ReportExporterServiceInterface $exporter,
        private readonly ReportRowBuilder $rowBuilder,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('path', InputArgument::REQUIRED, 'Target file path to write CSV');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $startedAt = microtime(true);
        $path = trim((string) $input->getArgument('path'));

        $from = new \DateTimeImmutable('first day of this month 00:00:00');
        $to = new \DateTimeImmutable('last day of this month 23:59:59');

        $request = new KpiRequest(
            vendorId: null,
            currency: null,
            from: $from->format('Y-m-d H:i:s'),
            to: $to->format('Y-m-d H:i:s'),
        );

        try {
            $normalizedPath = $this->normalizeTargetPath($path);

            $kpi = $this->dashboard->kpi($request);
            $series = $this->dashboard->timeseries($request);
            $rows = $this->rowBuilder->build($request, $kpi, $series);

            $this->exporter->exportToPath($rows, $normalizedPath);

            $this->logger->info('Analytics CSV export completed.', [
                'path' => $normalizedPath,
                'rows' => count($rows),
                'series_rows' => count($series),
                'duration_ms' => max(0, (int) round((microtime(true) - $startedAt) * 1000)),
            ]);

            $output->writeln('<info>CSV exported to '.$normalizedPath.'</info>');

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->logger->error('Analytics CSV export failed.', [
                'exception' => $exception,
                'path' => $path,
                'duration_ms' => max(0, (int) round((microtime(true) - $startedAt) * 1000)),
            ]);
            $output->writeln('<error>CSV export failed: '.$exception->getMessage().'</error>');

            return self::FAILURE;
        }
    }

    private function normalizeTargetPath(string $path): string
    {
        if ($path === '') {
            throw new \InvalidArgumentException('Export path must not be empty.');
        }

        if (strlen($path) > self::MAX_TARGET_PATH_LENGTH) {
            throw new \InvalidArgumentException('Export path too long.');
        }

        if (pathinfo($path, PATHINFO_EXTENSION) === '') {
            $path .= '.csv';
        }

        return $path;
    }
}
