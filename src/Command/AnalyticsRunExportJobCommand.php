<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Analytics\ExportJob;
use App\Service\Analytics\ExportJobRunner;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'analytics:job:run')]
final class AnalyticsRunExportJobCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ExportJobRunner $runner,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('id', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = (int) $input->getArgument('id');
        $job = $this->em->getRepository(ExportJob::class)->find($id);

        if (!$job instanceof ExportJob) {
            $output->writeln('Job not found');
            return self::FAILURE;
        }

        $this->runner->run($job);

        $output->writeln('done');

        return self::SUCCESS;
    }
}
