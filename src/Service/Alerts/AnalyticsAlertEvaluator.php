<?php

declare(strict_types=1);

namespace App\Analysing\Service\Alerts;

use App\Analysing\Entity\Alerts\AnalyticsAlertRuleEntity;
use App\Analysing\Entity\Analytics\AnalyticsMetricSnapshotEntity;
use App\Analysing\RepositoryInterface\AnalyticsMetricSnapshotRepositoryInterface;
use App\Analysing\ServiceInterface\Alerts\AnalyticsAlertEvaluatorInterface;
use Psr\Log\LoggerInterface;

final readonly class AnalyticsAlertEvaluator implements AnalyticsAlertEvaluatorInterface
{
    public function __construct(
        private AnalyticsMetricSnapshotRepositoryInterface $snapshots,
        private LoggerInterface $logger,
    ) {
    }

    /** @return array<array{rule: AnalyticsAlertRuleEntity, matched: bool, snapshot?: AnalyticsMetricSnapshotEntity}> */
    public function evaluate(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        if ($from > $to) {
            throw new \InvalidArgumentException('Alert evaluation range is invalid.');
        }

        $rules = $this->loadRules($from, $to);
        $out = [];

        $matchedCount = 0;
        $evaluatedCount = 0;

        foreach ($rules as $rule) {
            ++$evaluatedCount;
            $result = $this->evaluateRule($rule, $from, $to);
            if ($result['matched']) {
                ++$matchedCount;
            }
            $out[] = $result;
        }

        $this->logger->info('Analytics alert evaluation completed.', [
            'from' => $from->format(DATE_ATOM),
            'to' => $to->format(DATE_ATOM),
            'evaluated_rules' => $evaluatedCount,
            'matched_rules' => $matchedCount,
        ]);

        return $out;
    }

    /** @return list<AnalyticsAlertRuleEntity> */
    private function loadRules(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        try {
            return $this->snapshots->findActiveAlertRules();
        } catch (\Throwable $exception) {
            $this->logger->error('Analytics alert rules could not be loaded.', [
                'exception' => $exception,
                'from' => $from->format(DATE_ATOM),
                'to' => $to->format(DATE_ATOM),
            ]);

            throw new \RuntimeException('Analytics alert rules are unavailable.', 0, $exception);
        }
    }

    /** @return array{rule: AnalyticsAlertRuleEntity, matched: bool, snapshot?: AnalyticsMetricSnapshotEntity} */
    private function evaluateRule(
        AnalyticsAlertRuleEntity $rule,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
    ): array {
        $condition = $rule->getCondition();
        $metricValue = $condition['metric'] ?? '';
        $metric = is_scalar($metricValue) ? trim((string) $metricValue) : '';
        $operatorValue = $condition['operator'] ?? '';
        $operator = is_scalar($operatorValue) ? trim((string) $operatorValue) : '';
        $threshold = $condition['value'] ?? null;

        if ('' === $metric || '' === $operator || !is_numeric($threshold)) {
            $this->logger->warning('Alert rule invalid.', [
                'code' => $rule->getCode(),
                'condition' => $condition,
            ]);

            return ['rule' => $rule, 'matched' => false];
        }

        if (!in_array($operator, ['>', '>=', '<', '<=', '==', '!='], true)) {
            $this->logger->warning('Alert rule operator unsupported.', [
                'code' => $rule->getCode(),
                'operator' => $operator,
            ]);

            return ['rule' => $rule, 'matched' => false];
        }

        $snapshot = $this->findLatestSnapshotInRange($metric, $from, $to, $rule->getCode());
        if (!$snapshot instanceof AnalyticsMetricSnapshotEntity) {
            $this->logger->info('Alert evaluation skipped because no snapshot matched the range.', [
                'code' => $rule->getCode(),
                'metric' => $metric,
                'from' => $from->format(DATE_ATOM),
                'to' => $to->format(DATE_ATOM),
            ]);

            return ['rule' => $rule, 'matched' => false];
        }

        return [
            'rule' => $rule,
            'matched' => $this->matches($snapshot->getValue(), (float) $threshold, $operator),
            'snapshot' => $snapshot,
        ];
    }

    private function matches(float $value, float $target, string $operator): bool
    {
        return match ($operator) {
            '>' => $value > $target,
            '>=' => $value >= $target,
            '<' => $value < $target,
            '<=' => $value <= $target,
            '==' => $value == $target,
            '!=' => $value != $target,
            default => false,
        };
    }

    private function findLatestSnapshotInRange(string $metric, \DateTimeImmutable $from, \DateTimeImmutable $to, string $ruleCode): ?AnalyticsMetricSnapshotEntity
    {
        try {
            $result = $this->snapshots->findLatestInRange($metric, $from, $to);
        } catch (\Throwable $exception) {
            $this->logger->error('Analytics alert snapshot lookup failed.', [
                'exception' => $exception,
                'rule' => $ruleCode,
                'metric' => $metric,
                'from' => $from->format(DATE_ATOM),
                'to' => $to->format(DATE_ATOM),
            ]);

            throw new \RuntimeException('Analytics alert snapshots are unavailable.', 0, $exception);
        }

        return $result instanceof AnalyticsMetricSnapshotEntity ? $result : null;
    }
}
