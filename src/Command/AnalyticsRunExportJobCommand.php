<?php

declare(strict_types=1);

namespace App\Analysing\Command;

use App\Analysing\Entity\Analytics\AnalyticsExportJobEntity;
use App\Analysing\Service\AnalyticsExportJobLockManager;
use App\Analysing\Service\AnalyticsExportJobRunner;
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
        private readonly AnalyticsExportJobRunner $runner,
        private readonly AnalyticsExportJobLockManager $lockManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('id', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $idArgument = $input->getArgument('id');
        if (!is_scalar($idArgument) && null !== $idArgument) {
            throw new \InvalidArgumentException('Export job id must be scalar.');
        }
        $id = (int) $idArgument;
        $job = $this->em->getRepository(AnalyticsExportJobEntity::class)->find($id);

        if (!$job instanceof AnalyticsExportJobEntity) {
            $output->writeln('Job not found');

            return self::FAILURE;
        }

        $lock = $this->lockManager->acquire($id);
        if (null === $lock) {
            $output->writeln('Job is already locked');

            return self::SUCCESS;
        }

        try {
            $this->runner->run($job);
        } finally {
            $this->lockManager->release($lock);
        }

        $output->writeln('done');

        return self::SUCCESS;
    }
}
