<?php

declare(strict_types=1);

namespace App\Analysing\Command;

use App\Analysing\Entity\Alerts\AnalyticsAlertRuleEntity;
use App\Analysing\Entity\Analytics\AnalyticsMetricSnapshotEntity;
use App\Analysing\ServiceInterface\Alerts\AnalyticsAlertEvaluatorInterface;
use App\Analysing\ServiceInterface\Alerts\AnalyticsNotificationDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'analytics:alerts:run', description: 'Evaluate analytics alert rules and dispatch notifications')]
final class AnalyticsAlertsRunCommand extends BaseCommand
{
    public function __construct(
        private readonly AnalyticsAlertEvaluatorInterface $evaluator,
        private readonly AnalyticsNotificationDispatcherInterface $dispatcher,
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
            $from = $to->sub(new \DateInterval('PT15M'));
            $fromLabel = $from->format(DATE_ATOM);
            $toLabel = $to->format(DATE_ATOM);
            $results = $this->evaluator->evaluate($from, $to);
            $evaluatedCount = count($results);
            [$matchedCount, $dispatchedCount, $failedDispatchCount, $skippedMalformedCount] = $this->processResults($results);

            $this->logger->info('Analytics alerts run completed.', [
                'from' => $fromLabel,
                'to' => $toLabel,
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
                'from' => $fromLabel,
                'to' => $toLabel,
                'duration_ms' => max(0, (int) round((microtime(true) - $startedAtFloat) * 1000)),
            ]);
            $output->writeln('<error>Alerts evaluation failed: '.$exception->getMessage().'</error>');

            return self::FAILURE;
        }
    }

    /**
     * @param array<mixed> $results
     *
     * @return array{int, int, int, int}
     */
    private function processResults(array $results): array
    {
        $matchedCount = 0;
        $dispatchedCount = 0;
        $failedDispatchCount = 0;
        $skippedMalformedCount = 0;

        foreach ($results as $index => $result) {
            if (!is_array($result)) {
                ++$skippedMalformedCount;
                $this->logger->warning('Analytics alerts run skipped a malformed evaluator result.', [
                    'result_index' => $index,
                    'result_type' => get_debug_type($result),
                ]);
                continue;
            }

            if (true !== $result['matched']) {
                continue;
            }

            $rule = $result['rule'];
            $snapshot = $result['snapshot'] ?? null;
            if (!$rule instanceof AnalyticsAlertRuleEntity || !$snapshot instanceof AnalyticsMetricSnapshotEntity) {
                ++$skippedMalformedCount;
                $this->logger->warning('Analytics alerts run skipped a matched result with an invalid rule or snapshot.', [
                    'result_index' => $index,
                    'rule_type' => get_debug_type($rule),
                    'snapshot_type' => get_debug_type($snapshot),
                ]);
                continue;
            }

            ++$matchedCount;
            $message = $this->buildMessage($rule, $snapshot);

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

        return [$matchedCount, $dispatchedCount, $failedDispatchCount, $skippedMalformedCount];
    }

    private function buildMessage(
        AnalyticsAlertRuleEntity $rule,
        AnalyticsMetricSnapshotEntity $snapshot,
    ): string {
        return sprintf(
            'Rule "%s" matched on %s=%s',
            $rule->getCode(),
            $snapshot->getMetric(),
            $snapshot->getValue(),
        );
    }
}
