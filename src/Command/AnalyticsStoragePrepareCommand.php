<?php

declare(strict_types=1);

namespace App\Command;

use App\Infrastructure\Doctrine\AnalyticsStorageManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'analytics:storage:prepare', description: 'Prepare analytics database schema and optional seed data')]
final class AnalyticsStoragePrepareCommand extends BaseCommand
{
    public function __construct(
        private readonly AnalyticsStorageManager $storage,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('seed', null, InputOption::VALUE_NONE, 'Insert development seed data after schema preparation');
        $this->addOption('inspect-only', null, InputOption::VALUE_NONE, 'Inspect storage readiness without changing schema');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $inspectOnly = $input->getOption('inspect-only');
        $seed = $input->getOption('seed');
        if (!is_bool($inspectOnly) || !is_bool($seed)) {
            throw new \RuntimeException('Console options must resolve to booleans.');
        }

        try {
            if ($inspectOnly) {
                $inspection = $this->storage->inspect();
                $output->writeln('<info>Analytics storage inspection completed.</info>');
                $output->writeln('mode: '.$inspection['mode']);
                $output->writeln('path: '.($inspection['path'] ?? 'n/a'));
                $output->writeln('ready: '.($inspection['ready'] ? 'yes' : 'no'));
                $output->writeln('missing_tables: '.([] === $inspection['missing_tables'] ? 'none' : implode(', ', $inspection['missing_tables'])));

                return $inspection['ready'] ? self::SUCCESS : self::FAILURE;
            }

            $result = $this->storage->prepare($seed);
            $output->writeln('<info>Analytics storage prepared.</info>');
            $output->writeln('mode: '.$result['mode']);
            $output->writeln('path: '.($result['path'] ?? 'n/a'));
            $output->writeln('executed_sql_count: '.(string) $result['executed_sql_count']);
            $output->writeln('seeded: '.($result['seeded'] ? 'yes' : 'no'));
            $output->writeln('missing_tables: '.([] === $result['missing_tables'] ? 'none' : implode(', ', $result['missing_tables'])));

            return [] === $result['missing_tables'] ? self::SUCCESS : self::FAILURE;
        } catch (\Throwable $exception) {
            $this->logger->error('Analytics storage command failed.', [
                'exception' => $exception,
                'seed' => $seed,
                'inspect_only' => $inspectOnly,
            ]);
            $output->writeln('<error>'.$exception->getMessage().'</error>');

            return self::FAILURE;
        }
    }
}
