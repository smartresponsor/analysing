<?php

declare(strict_types=1);

namespace App\Analysing\Command;

use App\Analysing\Entity\Analytics\AnalyticsExportJobEntity;
use App\Analysing\Service\AnalyticsExportJobLockManager;
use App\Analysing\Service\AnalyticsExportJobRunner;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Processes pending and retryable export jobs in bounded batches.
 *
 * This command is intended for scheduled execution and runs two passes: first over jobs waiting in
 * the pending state and then over failed jobs that are still eligible for retry.
 */
#[AsCommand(name: 'analytics:job:run-pending')]
final class AnalyticsRunPendingExportJobsCommand extends Command
{
    private const int MAX_JOBS_PER_RUN = 10;

    /**
     * @param EntityManagerInterface        $em          entity manager used to query export jobs
     * @param AnalyticsExportJobRunner      $runner      runner that executes the actual export workflow
     * @param AnalyticsExportJobLockManager $lockManager lock manager that prevents duplicate job execution
     */
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AnalyticsExportJobRunner $runner,
        private readonly AnalyticsExportJobLockManager $lockManager,
    ) {
        parent::__construct();
    }

    /**
     * Executes a single worker iteration for pending and retryable export jobs.
     *
     * @return int console exit code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $jobs = $this->em->getRepository(AnalyticsExportJobEntity::class)->findBy(
            ['status' => AnalyticsExportJobEntity::STATUS_PENDING],
            ['created_at' => 'ASC'],
            self::MAX_JOBS_PER_RUN
        );

        foreach ($jobs as $job) {
            $lock = $this->lockManager->acquire($job->getId());
            if (null === $lock) {
                continue;
            }

            try {
                $this->runner->run($job);
            } finally {
                $this->lockManager->release($lock);
            }
        }

        $failedJobs = $this->em->getRepository(AnalyticsExportJobEntity::class)->findBy(
            ['status' => AnalyticsExportJobEntity::STATUS_FAILED],
            ['created_at' => 'ASC'],
            self::MAX_JOBS_PER_RUN
        );

        foreach ($failedJobs as $job) {
            if (!$job->canRetry()) {
                continue;
            }

            $lock = $this->lockManager->acquire($job->getId());
            if (null === $lock) {
                continue;
            }

            try {
                $this->runner->run($job);
            } finally {
                $this->lockManager->release($lock);
            }
        }

        $output->writeln('done');

        return self::SUCCESS;
    }
}
