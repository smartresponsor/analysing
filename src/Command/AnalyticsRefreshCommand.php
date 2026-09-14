<?php

declare(strict_types=1);

namespace App\Analysing\Command;

use App\Analysing\ServiceInterface\AnalyticsCollectorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'analytics:refresh', description: 'Refresh basic KPI snapshots from latest events')]
final class AnalyticsRefreshCommand extends BaseCommand
{
    private const string METRIC = 'orders';

    public function __construct(
        private readonly AnalyticsCollectorInterface $collector,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $startedAtFloat = microtime(true);
        $fromLabel = null;
        $toLabel = null;

        try {
            $to = new \DateTimeImmutable();
            $from = $to->sub(new \DateInterval('PT1M'));
            $fromLabel = $from->format(DATE_ATOM);
            $toLabel = $to->format(DATE_ATOM);
            $this->collector->record(self::METRIC, 0.0, $from, $to, [
                'refresh_source' => 'command',
                'window_seconds' => $to->getTimestamp() - $from->getTimestamp(),
                'command' => 'analytics:refresh',
            ]);

            $this->logger->info('Analytics refresh command completed.', [
                'metric' => self::METRIC,
                'from' => $fromLabel,
                'to' => $toLabel,
                'duration_ms' => max(0, (int) round((microtime(true) - $startedAtFloat) * 1000)),
            ]);
            $output->writeln('<info>Analytics refreshed.</info>');

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->logger->error('Analytics refresh failed.', [
                'exception' => $exception,
                'metric' => self::METRIC,
                'from' => $fromLabel,
                'to' => $toLabel,
                'duration_ms' => max(0, (int) round((microtime(true) - $startedAtFloat) * 1000)),
            ]);
            $output->writeln('<error>Analytics refresh failed: '.$exception->getMessage().'</error>');

            return self::FAILURE;
        }
    }
}
