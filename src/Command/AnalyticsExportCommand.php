<?php
declare(strict_types=1);

namespace App\Command;

use App\Service\Analytics\ReportGeneratorService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:analytics:export', description: 'Export analytics report to CSV/XLSX')]
final class AnalyticsExportCommand extends Command
{
    public function __construct(private readonly ReportGeneratorService $generator)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            .addArgument('from', InputArgument::REQUIRED)
            .addArgument('to', InputArgument::REQUIRED)
            .addOption('vendor', null, InputOption::VALUE_REQUIRED)
            .addOption('currency', null, InputOption::VALUE_REQUIRED)
            .addOption('format', null, InputOption::VALUE_REQUIRED, 'csv');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $params = [
            'from' => (string)$input->getArgument('from'),
            'to' => (string)$input->getArgument('to'),
            'vendorId' => $input->getOption('vendor') !== null ? (int)$input->getOption('vendor') : null,
            'currency' => $input->getOption('currency') !== null ? (string)$input->getOption('currency') : null,
            'format' => (string)$input->getOption('format'),
        ];

        $job = $this->generator->generate($params);
        $output->writeln(sprintf('<info>Status:</info> %s', (new \ReflectionProperty($job, 'status'))->getValue($job)));
        $fileProp = new \ReflectionProperty($job, 'filePath'); $fileProp->setAccessible(true);
        $path = $fileProp->getValue($job);
        if ($path) {
            $output->writeln(sprintf('<info>File:</info> %s', $path));
        }
        return Command::SUCCESS;
    }
}
