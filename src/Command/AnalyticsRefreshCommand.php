<?php declare(strict_types=1);
namespace App\Command;
use App\Service\Analytics\AnalyticsCollector;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'analytics:refresh', description: 'Refresh basic KPI snapshots from latest events')]
final class AnalyticsRefreshCommand extends BaseCommand
{
    public function __construct(private readonly AnalyticsCollector $collector) { parent::__construct(); }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $to = new \DateTimeImmutable('now'); $from = $to->modify('-1 minute');
        $this->collector->record('orders', 0.0, $from, $to);
        $output->writeln('<info>Analytics refreshed.</info>'); return self::SUCCESS;
    }
}
