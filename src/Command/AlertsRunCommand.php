<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Alerts\AlertRule;
use App\Entity\Analytics\MetricSnapshot;
use App\ServiceInterface\Alerts\AlertEvaluatorInterface;
use App\ServiceInterface\Alerts\NotificationDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'analytics:alerts:run', description: 'Evaluate analytics alert rules and dispatch notifications')]
final class AlertsRunCommand extends BaseCommand
{
    public function __construct(
        private readonly AlertEvaluatorInterface $evaluator,
        private readonly NotificationDispatcherInterface $dispatcher,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $startedAt = new \DateTimeImmutable('now');
        $startedAtFloat = microtime(true);
        $from = $startedAt->modify('-15 minutes');
        $to = $startedAt;

        try {
            $results = $this->evaluator->evaluate($from, $to);
            $matchedCount = 0;
            $dispatchedCount = 0;
            $failedDispatchCount = 0;
            $skippedMalformedCount = 0;

            $evaluatedCount = count($results);

            foreach ($results as $index => $result) {
                if (!is_array($result)) {
                    ++$skippedMalformedCount;
                    $this->logger->warning('Analytics alerts run skipped a malformed evaluator result.', [
                        'result_index' => $index,
                        'result_type' => get_debug_type($result),
                    ]);
                    continue;
                }

                if ($result['matched'] !== true) {
                    continue;
                }

                $rule = $result['rule'];
                $snapshot = $result['snapshot'] ?? null;
                if (!$rule instanceof AlertRule || !$snapshot instanceof MetricSnapshot) {
                    ++$skippedMalformedCount;
                    $this->logger->warning('Analytics alerts run skipped a matched result with an invalid rule or snapshot.', [
                        'result_index' => $index,
                        'rule_type' => get_debug_type($rule),
                        'snapshot_type' => get_debug_type($snapshot),
                    ]);
                    continue;
                }

                ++$matchedCount;
                $message = sprintf(
                    'Rule "%s" matched on %s=%s',
                    $rule->getCode(),
                    $snapshot->getMetric(),
                    (string) $snapshot->getValue(),
                );

                try {
                    $this->dispatcher->dispatch($rule, $message);
                    ++$dispatchedCount;
                } catch (\Throwable $exception) {
                    ++$failedDispatchCount;
                    $this->logger->error('Analytics alert dispatch failed.', [
                        'exception' => $exception,
                        'rule' => $rule->getCode(),
                        'message' => $message,
                    ]);
                }
            }

            $this->logger->info('Analytics alerts run completed.', [
                'from' => $from->format(DATE_ATOM),
                'to' => $to->format(DATE_ATOM),
                'evaluated' => $evaluatedCount,
                'matched' => $matchedCount,
                'dispatched' => $dispatchedCount,
                'failed' => $failedDispatchCount,
                'skipped_malformed' => $skippedMalformedCount,
                'duration_ms' => max(0, (int) round((microtime(true) - $startedAtFloat) * 1000)),
            ]);
            $output->writeln(sprintf(
                '<info>Alerts evaluation finished. matched=%d dispatched=%d failed=%d skipped=%d</info>',
                $matchedCount,
                $dispatchedCount,
                $failedDispatchCount,
                $skippedMalformedCount,
            ));

            return $failedDispatchCount > 0 ? self::FAILURE : self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->logger->error('Analytics alerts run failed.', [
                'exception' => $exception,
                'from' => $from->format(DATE_ATOM),
                'to' => $to->format(DATE_ATOM),
                'duration_ms' => max(0, (int) round((microtime(true) - $startedAtFloat) * 1000)),
            ]);
            $output->writeln('<error>Alerts evaluation failed: '.$exception->getMessage().'</error>');

            return self::FAILURE;
        }
    }
}
