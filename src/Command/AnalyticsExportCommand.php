<?php

declare(strict_types=1);

namespace App\Command;

use App\DTO\Analytics\KpiRequest;
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

            $targetDir = dirname($normalizedPath);
            if ('' !== $targetDir && '.' !== $targetDir && !is_dir($targetDir) && !mkdir($targetDir, 0777, true) && !is_dir($targetDir)) {
                throw new \RuntimeException('Cannot create export directory: '.$targetDir);
            }
            if ('' !== $targetDir && '.' !== $targetDir && is_dir($targetDir) && !is_writable($targetDir)) {
                throw new \RuntimeException('Export directory is not writable: '.$targetDir);
            }

            $generatedPath = $this->exporter->export($rows, 'csv', '' === $targetDir ? null : $targetDir);
            $this->moveExportToTarget($generatedPath, $normalizedPath);

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
        if ('' === $path) {
            throw new \InvalidArgumentException('Export path must not be empty.');
        }
        if (strlen($path) > self::MAX_TARGET_PATH_LENGTH) {
            throw new \InvalidArgumentException('Export path exceeds the maximum supported length.');
        }

        $normalized = $path;
        $extension = strtolower(pathinfo($normalized, PATHINFO_EXTENSION));
        if ('' === $extension) {
            $normalized .= '.csv';
            $extension = 'csv';
        }

        if ('csv' !== $extension) {
            throw new \InvalidArgumentException('Analytics CSV export target path must use the .csv extension.');
        }

        return $normalized;
    }

    private function attemptRename(string $generatedPath, string $targetPath): bool
    {
        if (rename($generatedPath, $targetPath)) {
            return true;
        }

        clearstatcache(true, $targetPath);
        if (is_file($targetPath)) {
            $this->logger->warning('Analytics CSV export rename reported failure but target file exists; continuing with target path.', [
                'generated_path' => $generatedPath,
                'target_path' => $targetPath,
            ]);

            if (is_file($generatedPath) && !unlink($generatedPath)) {
                $this->logger->warning('Analytics CSV export could not remove generated file after rename fallback detection.', [
                    'generated_path' => $generatedPath,
                    'target_path' => $targetPath,
                ]);
            }

            return true;
        }

        $this->logger->warning('Analytics CSV export rename failed; falling back to stream copy.', [
            'generated_path' => $generatedPath,
            'target_path' => $targetPath,
        ]);

        return false;
    }

    private function moveExportToTarget(string $generatedPath, string $targetPath): void
    {
        if ('' === $generatedPath || !is_file($generatedPath)) {
            throw new \RuntimeException('Generated export file is missing: '.$generatedPath);
        }

        if (is_file($targetPath) && !is_writable($targetPath)) {
            throw new \RuntimeException('Target export file is not writable: '.$targetPath);
        }

        if ($this->attemptRename($generatedPath, $targetPath)) {
            return;
        }

        $source = fopen($generatedPath, 'r');
        if (false === $source) {
            throw new \RuntimeException('Cannot open generated export for reading: '.$generatedPath);
        }

        $target = fopen($targetPath, 'w');
        if (false === $target) {
            fclose($source);
            throw new \RuntimeException('Cannot open target export for writing: '.$targetPath);
        }

        try {
            $copied = stream_copy_to_stream($source, $target);
            if (false === $copied) {
                throw new \RuntimeException('Cannot copy generated export to target path: '.$targetPath);
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
