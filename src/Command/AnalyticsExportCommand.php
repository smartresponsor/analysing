<?php declare(strict_types=1);
namespace App\Command;
use App\Entity\Analytics\ExportJob;
use App\Service\Analytics\DashboardService;
use App\Service\Analytics\ReportExporterService;
use App\Service\Analytics\ReportGeneratorService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'analytics:export:csv', description: 'Export KPI aggregates to CSV')]
final class AnalyticsExportCommand extends BaseCommand
{
    public function __construct(private readonly DashboardService $dashboard, private readonly ReportGeneratorService $generator, private readonly ReportExporterService $exporter) { parent::__construct(); }
    protected function configure(): void { $this->addArgument('path', InputArgument::REQUIRED, 'Target file path to write CSV'); }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = (string)$input->getArgument('path');
        $from = new \DateTimeImmutable('first day of this month 00:00:00'); $to = new \DateTimeImmutable('last day of this month 23:59:59');
        $agg = [ $this->dashboard->aggregate('orders', $from, $to), $this->dashboard->aggregate('revenue', $from, $to) ];
        $csv = $this->generator->generateCsv($agg);
        $job = new ExportJob('csv', ['path'=>$path]); $this->exporter->exportCsv($job, $csv);
        $output->writeln('<info>CSV exported to ' . $path . '</info>'); return self::SUCCESS;
    }
}
