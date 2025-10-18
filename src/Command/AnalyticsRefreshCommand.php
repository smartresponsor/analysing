<?php
declare(strict_types=1);

namespace App\Command;

use App\Service\Analytics\AnalyticsCollector;
use MongoDB\Driver\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:analytics:refresh', description: 'Refresh metric snapshots from ledger and finance data')]
final class AnalyticsRefreshCommand extends Command
{
    public function __construct(private readonly AnalyticsCollector $collector)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $count = $this->collector->refresh();
        $output->writeln("<info>Refreshed {$count} metric snapshots</info>");
        return Command::SUCCESS;
    }
}
