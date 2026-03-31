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

#[AsCommand(name: 'analytics:export:csv')]
final class AnalyticsExportCommand extends BaseCommand
{
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
        $this->addArgument('path', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = (string) $input->getArgument('path');

        $request = new KpiRequest(null, null, null, null);
        $rows = $this->rowBuilder->build($request, ['gross_minor'=>0,'net_minor'=>0,'days'=>0], []);

        $this->exporter->exportToPath($rows, $path, 'csv');

        $output->writeln('done');

        return self::SUCCESS;
    }
}
