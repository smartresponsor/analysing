<?php

declare(strict_types=1);

namespace App\Command;

use App\ServiceInterface\Analytics\AnalyticsCollectorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'analytics:refresh', description: 'Refresh basic KPI snapshots from latest events')]
final class AnalyticsRefreshCommand extends BaseCommand
{
    private const METRIC = 'orders';

    public function __construct(
        private readonly AnalyticsCollectorInterface $collector,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $startedAt = new \DateTimeImmutable('now');
        $startedAtFloat = microtime(true);
        $to = $startedAt;
        $from = $to->modify('-1 minute');

        try {
            $this->collector->record(self::METRIC, 0.0, $from, $to, [
                'refresh_source' => 'command',
                'window_seconds' => $to->getTimestamp() - $from->getTimestamp(),
                'command' => 'analytics:refresh',
            ]);

            $this->logger->info('Analytics refresh command completed.', [
                'metric' => self::METRIC,
                'from' => $from->format(DATE_ATOM),
                'to' => $to->format(DATE_ATOM),
                'duration_ms' => max(0, (int) round((microtime(true) - $startedAtFloat) * 1000)),
            ]);
            $output->writeln('<info>Analytics refreshed.</info>');

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->logger->error('Analytics refresh failed.', [
                'exception' => $exception,
                'metric' => self::METRIC,
                'from' => $from->format(DATE_ATOM),
                'to' => $to->format(DATE_ATOM),
                'duration_ms' => max(0, (int) round((microtime(true) - $startedAtFloat) * 1000)),
            ]);
            $output->writeln('<error>Analytics refresh failed: '.$exception->getMessage().'</error>');

            return self::FAILURE;
        }
    }
}
