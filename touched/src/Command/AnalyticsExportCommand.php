<?php
declare(strict_types=1);

namespace App\Command;

use App\DTO\Analytics\KpiRequest;
use App\ServiceInterface\Analytics\DashboardServiceInterface;
use App\ServiceInterface\Analytics\ReportExporterServiceInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

#[AsCommand(name: 'analytics:export:csv', description: 'Export KPI aggregates to CSV')]
final class AnalyticsExportCommand extends BaseCommand
{
    public function __construct(
        private readonly DashboardServiceInterface $dashboard,
        private readonly ReportExporterServiceInterface $exporter,
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
            if ($path === '') {
                throw new InvalidArgumentException('Export path must not be empty.');
            }

            $kpi = $this->dashboard->kpi($request);
            $series = $this->dashboard->timeseries($request);
            $rows = [[
                'section' => 'totals',
                'from' => $request->from,
                'to' => $request->to,
                'vendor_id' => '',
                'currency' => '',
                'gross_minor' => $kpi['gross_minor'],
                'net_minor' => $kpi['net_minor'],
                'margin_pct' => $kpi['margin_pct'],
                'days' => $kpi['days'],
            ]];

            foreach ($series as $point) {
                $rows[] = [
                    'section' => 'timeseries',
                    'date' => $point['date'],
                    'gross_minor' => $point['gross_minor'],
                    'net_minor' => $point['net_minor'],
                ];
            }

            $targetDir = dirname($path);
            if ($targetDir !== '' && $targetDir !== '.' && !is_dir($targetDir) && !mkdir($targetDir, 0777, true) && !is_dir($targetDir)) {
                throw new RuntimeException('Cannot create export directory: ' . $targetDir);
            }

            $generatedPath = $this->exporter->export($rows, 'csv', $targetDir === '' ? null : $targetDir);
            $this->moveExportToTarget($generatedPath, $path);

            $output->writeln('<info>CSV exported to ' . $path . '</info>');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->logger->error('Analytics CSV export failed.', [
                'exception' => $exception,
                'path' => $path,
            ]);
            $output->writeln('<error>CSV export failed: ' . $exception->getMessage() . '</error>');

            return self::FAILURE;
        }
    }

    private function moveExportToTarget(string $generatedPath, string $targetPath): void
    {
        if ($generatedPath === '' || !is_file($generatedPath)) {
            throw new RuntimeException('Generated export file is missing: ' . $generatedPath);
        }

        if (is_file($targetPath) && !is_writable($targetPath)) {
            throw new RuntimeException('Target export file is not writable: ' . $targetPath);
        }

        if (@rename($generatedPath, $targetPath)) {
            return;
        }

        $source = fopen($generatedPath, 'rb');
        if ($source === false) {
            throw new RuntimeException('Cannot open generated export for reading: ' . $generatedPath);
        }

        $target = fopen($targetPath, 'wb');
        if ($target === false) {
            fclose($source);
            throw new RuntimeException('Cannot open target export for writing: ' . $targetPath);
        }

        try {
            $copied = stream_copy_to_stream($source, $target);
            if ($copied === false) {
                throw new RuntimeException('Cannot copy generated export to target path: ' . $targetPath);
            }
        } finally {
            fclose($source);
            fclose($target);
        }

        if (!unlink($generatedPath)) {
            $this->logger->warning('Analytics CSV export could not remove temporary generated file.', [
                'generated_path' => $generatedPath,
                'target_path' => $targetPath,
            ]);
        }
    }
}
