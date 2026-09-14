<?php

declare(strict_types=1);

namespace App\Analysing\Service\Alerts;

use App\Analysing\Entity\Alerts\AnalyticsAlertRuleEntity;
use App\Analysing\Entity\Analytics\AnalyticsMetricSnapshotEntity;
use App\Analysing\ServiceInterface\Alerts\AnalyticsAlertEvaluatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final readonly class AnalyticsAlertEvaluator implements AnalyticsAlertEvaluatorInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
    ) {
    }

    /** @return array<array{rule: AnalyticsAlertRuleEntity, matched: bool, snapshot?: AnalyticsMetricSnapshotEntity}> */
    public function evaluate(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        if ($from > $to) {
            throw new \InvalidArgumentException('Alert evaluation range is invalid.');
        }

        try {
            $rules = $this->em->getRepository(AnalyticsAlertRuleEntity::class)->findBy(['is_active' => true]);
        } catch (\Throwable $exception) {
            $this->logger->error('Analytics alert rules could not be loaded.', [
                'exception' => $exception,
                'from' => $from->format(DATE_ATOM),
                'to' => $to->format(DATE_ATOM),
            ]);

            throw new \RuntimeException('Analytics alert rules are unavailable.', 0, $exception);
        }

        $out = [];

        $matchedCount = 0;
        $evaluatedCount = 0;

        foreach ($rules as $rule) {
            ++$evaluatedCount;
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
                $out[] = ['rule' => $rule, 'matched' => false];
                continue;
            }

            if (!in_array($operator, ['>', '>=', '<', '<=', '==', '!='], true)) {
                $this->logger->warning('Alert rule operator unsupported.', [
                    'code' => $rule->getCode(),
                    'operator' => $operator,
                ]);
                $out[] = ['rule' => $rule, 'matched' => false];
                continue;
            }

            $snapshot = $this->findLatestSnapshotInRange($metric, $from, $to, $rule->getCode());
            if (!$snapshot instanceof AnalyticsMetricSnapshotEntity) {
                $this->logger->info('Alert evaluation skipped because no snapshot matched the range.', [
                    'code' => $rule->getCode(),
                    'metric' => $metric,
                    'from' => $from->format(DATE_ATOM),
                    'to' => $to->format(DATE_ATOM),
                ]);
                $out[] = ['rule' => $rule, 'matched' => false];
                continue;
            }

            $value = $snapshot->getValue();
            $target = (float) $threshold;
            $matched = match ($operator) {
                '>' => $value > $target,
                '>=' => $value >= $target,
                '<' => $value < $target,
                '<=' => $value <= $target,
                '==' => $value == $target,
                '!=' => $value != $target,
            };

            if ($matched) {
                ++$matchedCount;
            }

            $out[] = ['rule' => $rule, 'matched' => $matched, 'snapshot' => $snapshot];
        }

        $this->logger->info('Analytics alert evaluation completed.', [
            'from' => $from->format(DATE_ATOM),
            'to' => $to->format(DATE_ATOM),
            'evaluated_rules' => $evaluatedCount,
            'matched_rules' => $matchedCount,
        ]);

        return $out;
    }

    private function findLatestSnapshotInRange(string $metric, \DateTimeImmutable $from, \DateTimeImmutable $to, string $ruleCode): ?AnalyticsMetricSnapshotEntity
    {
        try {
            $qb = $this->em->createQueryBuilder();

            $query = $qb
                ->select('snapshot')
                ->from(AnalyticsMetricSnapshotEntity::class, 'snapshot')
                ->andWhere('snapshot.metric = :metric')
                ->andWhere('snapshot.period_start >= :from')
                ->andWhere('snapshot.period_end <= :to')
                ->orderBy('snapshot.period_end', 'DESC')
                ->addOrderBy('snapshot.period_start', 'DESC')
                ->setMaxResults(1)
                ->setParameter('metric', $metric)
                ->setParameter('from', $from)
                ->setParameter('to', $to)
                ->getQuery();

            $result = $query->getOneOrNullResult();
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
