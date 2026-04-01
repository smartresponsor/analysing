<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Analytics\ExportJob;
use App\Service\Analytics\ExportJobRunner;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'analytics:job:run-pending')]
final class AnalyticsRunPendingExportJobsCommand extends Command
{
    private const MAX_JOBS_PER_RUN = 10;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ExportJobRunner $runner,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $jobs = $this->em->getRepository(ExportJob::class)->findBy(
            ['status' => ExportJob::STATUS_PENDING],
            ['created_at' => 'ASC'],
            self::MAX_JOBS_PER_RUN
        );

        foreach ($jobs as $job) {
            $this->runner->run($job);
        }

        // retry failed
        $failedJobs = $this->em->getRepository(ExportJob::class)->findBy(
            ['status' => ExportJob::STATUS_FAILED],
            ['created_at' => 'ASC'],
            self::MAX_JOBS_PER_RUN
        );

        foreach ($failedJobs as $job) {
            if ($job->canRetry()) {
                $this->runner->run($job);
            }
        }

        $output->writeln('done');

        return self::SUCCESS;
    }
}
