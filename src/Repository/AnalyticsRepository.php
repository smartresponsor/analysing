<?php

declare(strict_types=1);
/*
 * Marketing America Corp. Oleksandr Tishchenko
 * Comments in English only. Singular naming.
 */

namespace App\Analysing\Repository;

use App\Analysing\Entity\Analytics\AnalyticsExperimentMetricDailyEntity;
use App\Analysing\Entity\Analytics\AnalyticsFunnelDailyEntity;
use App\Analysing\Entity\Analytics\AnalyticsMetricSnapshotEntity;
use App\Analysing\Entity\Analytics\AnalyticsPathTransitionDailyEntity;
use App\Analysing\Entity\Analytics\AnalyticsRetentionCohortDailyEntity;
use App\Analysing\RepositoryInterface\AnalyticsRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final readonly class AnalyticsRepository implements AnalyticsRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
    ) {
    }

    public function fetchFunnel(string $app, string $env, array $steps, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $app = $this->normalizeKey($app, 'app');
        $env = $this->normalizeKey($env, 'env');
        $steps = $this->normalizeStepList($steps);
        $this->assertOrderedDateRange($from, $to);

        $qb = $this->em->createQueryBuilder()
            ->select('daily.day AS day')
            ->addSelect('daily.userCount AS user_count')
            ->from(AnalyticsFunnelDailyEntity::class, 'daily')
            ->where('daily.app = :app')
            ->andWhere('daily.env = :env')
            ->andWhere('daily.day BETWEEN :from AND :to')
            ->andWhere('daily.step1 = :step1')
            ->andWhere('daily.step2 = :step2')
            ->orderBy('daily.day', 'ASC')
            ->setParameter('app', $app)
            ->setParameter('env', $env)
            ->setParameter('from', $from->format('Y-m-d'))
            ->setParameter('to', $to->format('Y-m-d'))
            ->setParameter('step1', $steps[0])
            ->setParameter('step2', $steps[1]);

        if (isset($steps[2])) {
            $qb->andWhere('daily.step3 = :step3');
            $qb->setParameter('step3', $steps[2]);
        }
        if (isset($steps[3])) {
            $qb->andWhere('daily.step4 = :step4');
            $qb->setParameter('step4', $steps[3]);
        }

        try {
            return $this->normalizeFunnelRows($this->normalizeScalarRows($qb->getQuery()->getScalarResult()));
        } catch (\Throwable $exception) {
            $this->logger->error('Analytics infra repository funnel query failed.', [
                'exception' => $exception,
                'app' => $app,
                'env' => $env,
                'steps' => $steps,
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
            ]);

            throw new \RuntimeException('Aggregate funnel repository query failed.', 0, $exception);
        }
    }

    public function fetchRetention(string $app, string $env, \DateTimeImmutable $cohort, int $days): array
    {
        $app = $this->normalizeKey($app, 'app');
        $env = $this->normalizeKey($env, 'env');
        $days = $this->normalizePositiveInteger($days, 'days');

        $qb = $this->em->createQueryBuilder()
            ->select('daily.dayOffset AS day_offset')
            ->addSelect('daily.activeUser AS active_user')
            ->from(AnalyticsRetentionCohortDailyEntity::class, 'daily')
            ->where('daily.app = :app')
            ->andWhere('daily.env = :env')
            ->andWhere('daily.cohort = :cohort')
            ->andWhere('daily.dayOffset <= :days')
            ->orderBy('daily.dayOffset', 'ASC')
            ->setParameter('app', $app)
            ->setParameter('env', $env)
            ->setParameter('cohort', $cohort->format('Y-m-d'))
            ->setParameter('days', $days);

        try {
            return $this->normalizeRetentionRows($this->normalizeScalarRows($qb->getQuery()->getScalarResult()));
        } catch (\Throwable $exception) {
            $this->logger->error('Analytics infra repository retention query failed.', [
                'exception' => $exception,
                'app' => $app,
                'env' => $env,
                'cohort' => $cohort->format('Y-m-d'),
                'days' => $days,
            ]);

            throw new \RuntimeException('Aggregate retention repository query failed.', 0, $exception);
        }
    }

    /**
     * @param list<string> $steps
     *
     * @return list<array{cohort_date:string,user_count:int}>
     */
    public function fetchCohort(string $app, string $env, array $steps, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $app = $this->normalizeKey($app, 'app');
        $env = $this->normalizeKey($env, 'env');
        $this->normalizeStepList($steps);
        $this->assertOrderedDateRange($from, $to);

        $qb = $this->em->createQueryBuilder()
            ->select('daily.cohort AS cohort_date')
            ->addSelect('SUM(daily.activeUser) AS user_count')
            ->from(AnalyticsRetentionCohortDailyEntity::class, 'daily')
            ->where('daily.app = :app')
            ->andWhere('daily.env = :env')
            ->andWhere('daily.dayOffset = 0')
            ->andWhere('daily.cohort BETWEEN :from AND :to')
            ->groupBy('daily.cohort')
            ->orderBy('daily.cohort', 'ASC')
            ->setParameter('app', $app)
            ->setParameter('env', $env)
            ->setParameter('from', $from->format('Y-m-d'))
            ->setParameter('to', $to->format('Y-m-d'));

        try {
            return $this->normalizeCohortRows($this->normalizeScalarRows($qb->getQuery()->getScalarResult()));
        } catch (\Throwable $exception) {
            $this->logger->error('Analytics infra repository cohort query failed.', [
                'exception' => $exception,
                'app' => $app,
                'env' => $env,
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
            ]);

            throw new \RuntimeException('Aggregate cohort repository query failed.', 0, $exception);
        }
    }

    public function fetchPath(string $app, string $env, \DateTimeImmutable $day, int $top): array
    {
        $app = $this->normalizeKey($app, 'app');
        $env = $this->normalizeKey($env, 'env');
        $top = $this->normalizePositiveInteger($top, 'top');

        $qb = $this->em->createQueryBuilder()
            ->select('daily.fromEvent AS from_event')
            ->addSelect('daily.toEvent AS to_event')
            ->addSelect('daily.transitionCount AS transition_count')
            ->from(AnalyticsPathTransitionDailyEntity::class, 'daily')
            ->where('daily.app = :app')
            ->andWhere('daily.env = :env')
            ->andWhere('daily.day = :day')
            ->orderBy('daily.transitionCount', 'DESC')
            ->setMaxResults($top)
            ->setParameter('app', $app)
            ->setParameter('env', $env)
            ->setParameter('day', $day->format('Y-m-d'));

        try {
            return $this->normalizePathRows($this->normalizeScalarRows($qb->getQuery()->getScalarResult()));
        } catch (\Throwable $exception) {
            $this->logger->error('Analytics infra repository path query failed.', [
                'exception' => $exception,
                'app' => $app,
                'env' => $env,
                'day' => $day->format('Y-m-d'),
                'top' => $top,
            ]);

            throw new \RuntimeException('Aggregate path repository query failed.', 0, $exception);
        }
    }

    /**
     * @return list<array{user_count:int}>
     */
    public function fetchAnomalySeries(string $metric, int $days): array
    {
        $metric = $this->normalizeKey($metric, 'metric');
        $days = $this->normalizePositiveInteger($days, 'days');

        $qb = $this->em->createQueryBuilder()
            ->select('snapshot.value AS user_count')
            ->from(AnalyticsMetricSnapshotEntity::class, 'snapshot')
            ->where('snapshot.metric = :metric')
            ->orderBy('snapshot.periodEnd', 'DESC')
            ->addOrderBy('snapshot.createdAt', 'DESC')
            ->setMaxResults($days)
            ->setParameter('metric', $metric);

        try {
            $rows = $this->normalizeSeriesRows($this->normalizeScalarRows($qb->getQuery()->getScalarResult()));

            return array_reverse($rows);
        } catch (\Throwable $exception) {
            $this->logger->error('Analytics infra repository anomaly series query failed.', [
                'exception' => $exception,
                'metric' => $metric,
                'days' => $days,
            ]);

            throw new \RuntimeException('Aggregate anomaly series repository query failed.', 0, $exception);
        }
    }

    public function fetchLatestMetricValue(string $metric): int
    {
        $metric = $this->normalizeKey($metric, 'metric');

        $qb = $this->em->createQueryBuilder()
            ->select('snapshot.value AS value')
            ->from(AnalyticsMetricSnapshotEntity::class, 'snapshot')
            ->where('snapshot.metric = :metric')
            ->orderBy('snapshot.periodEnd', 'DESC')
            ->addOrderBy('snapshot.createdAt', 'DESC')
            ->setMaxResults(1)
            ->setParameter('metric', $metric);

        try {
            $row = $this->normalizeScalarRows($qb->getQuery()->getScalarResult())[0] ?? null;
            if (null === $row) {
                return 0;
            }

            return $this->readRequiredInt($row, 'value', 'metric_tree', 0);
        } catch (\Throwable $exception) {
            $this->logger->error('Analytics infra repository latest metric query failed.', [
                'exception' => $exception,
                'metric' => $metric,
            ]);

            throw new \RuntimeException('Latest metric repository query failed.', 0, $exception);
        }
    }

    public function upsertAnalyticsExperimentMetricDailyEntity(string $experimentKey, string $variantKey, \DateTimeImmutable $day, int $exposure, int $conversion, float $valueSum): void
    {
        $experimentKey = $this->normalizeKey($experimentKey, 'experimentKey');
        $variantKey = $this->normalizeKey($variantKey, 'variantKey');

        if ($exposure < 0) {
            throw new \InvalidArgumentException('exposure must be zero or greater.');
        }

        if ($conversion < 0) {
            throw new \InvalidArgumentException('conversion must be zero or greater.');
        }

        try {
            /** @var AnalyticsExperimentMetricDailyEntity|null $entity */
            $entity = $this->em->getRepository(AnalyticsExperimentMetricDailyEntity::class)->findOneBy([
                'day' => $day->format('Y-m-d'),
                'experimentKey' => $experimentKey,
                'variantKey' => $variantKey,
            ]);

            if (!$entity instanceof AnalyticsExperimentMetricDailyEntity) {
                $entity = new AnalyticsExperimentMetricDailyEntity(
                    $day->format('Y-m-d'),
                    $experimentKey,
                    $variantKey,
                    $exposure,
                    $conversion,
                    $valueSum,
                );
                $this->em->persist($entity);
            } else {
                $entity->setExposure($exposure);
                $entity->setConversion($conversion);
                $entity->setValueSum($valueSum);
            }

            $this->em->flush();
        } catch (\Throwable $exception) {
            $this->logger->error('Analytics infra repository experiment upsert failed.', [
                'exception' => $exception,
                'day' => $day->format('Y-m-d'),
                'experiment_key' => $experimentKey,
                'variant_key' => $variantKey,
                'exposure' => $exposure,
                'conversion' => $conversion,
            ]);

            throw new \RuntimeException('AnalyticsExperiment metric upsert failed.', 0, $exception);
        }
    }

    /**
     * @param list<array<string,mixed>> $rows
     *
     * @return list<array{day:string,user_count:int}>
     */
    private function normalizeFunnelRows(array $rows): array
    {
        return array_map(function (mixed $row, int $index): array {
            return [
                'day' => $this->readRequiredString($row, 'day', 'funnel', $index),
                'user_count' => $this->readRequiredInt($row, 'user_count', 'funnel', $index),
            ];
        }, $rows, array_keys($rows));
    }

    /**
     * @param list<array<string,mixed>> $rows
     *
     * @return list<array{cohort_date:string,user_count:int}>
     */
    private function normalizeCohortRows(array $rows): array
    {
        return array_map(function (mixed $row, int $index): array {
            return [
                'cohort_date' => $this->readRequiredString($row, 'cohort_date', 'cohort', $index),
                'user_count' => $this->readRequiredInt($row, 'user_count', 'cohort', $index),
            ];
        }, $rows, array_keys($rows));
    }

    /**
     * @param list<array<string,mixed>> $rows
     *
     * @return list<array{user_count:int}>
     */
    private function normalizeSeriesRows(array $rows): array
    {
        return array_map(function (mixed $row, int $index): array {
            return [
                'user_count' => $this->readRequiredInt($row, 'user_count', 'series', $index),
            ];
        }, $rows, array_keys($rows));
    }

    /**
     * @param array<mixed> $rows
     *
     * @return list<array<string,mixed>>
     */
    private function normalizeScalarRows(array $rows): array
    {
        $normalized = [];
        foreach (array_values($rows) as $row) {
            $normalized[] = $this->normalizeScalarRow($row);
        }

        return $normalized;
    }

    /**
     * @param mixed $row
     *
     * @return array<string,mixed>
     */
    private function normalizeScalarRow(mixed $row): array
    {
        if (!is_array($row)) {
            throw new \RuntimeException('Analytics query row must be an array.');
        }

        $normalized = [];
        foreach ($row as $key => $value) {
            $normalized[(string) $key] = $value;
        }

        return $normalized;
    }

    /**
     * @param list<array<string,mixed>> $rows
     *
     * @return list<array{day_offset:int,active_user:int}>
     */
    private function normalizeRetentionRows(array $rows): array
    {
        return array_map(function (mixed $row, int $index): array {
            return [
                'day_offset' => $this->readRequiredInt($row, 'day_offset', 'retention', $index),
                'active_user' => $this->readRequiredInt($row, 'active_user', 'retention', $index),
            ];
        }, $rows, array_keys($rows));
    }

    /**
     * @param list<array<string,mixed>> $rows
     *
     * @return list<array{from_event:string,to_event:string,transition_count:int}>
     */
    private function normalizePathRows(array $rows): array
    {
        return array_map(function (mixed $row, int $index): array {
            return [
                'from_event' => $this->readRequiredString($row, 'from_event', 'path', $index),
                'to_event' => $this->readRequiredString($row, 'to_event', 'path', $index),
                'transition_count' => $this->readRequiredInt($row, 'transition_count', 'path', $index),
            ];
        }, $rows, array_keys($rows));
    }

    /**
     * @param array<string,mixed> $row
     */
    private function readRequiredString(array $row, string $field, string $query, int $index): string
    {
        if (!array_key_exists($field, $row) || !is_scalar($row[$field])) {
            $this->logger->error('Analytics infra repository row is missing a required scalar string field.', [
                'query' => $query,
                'row_index' => $index,
                'field' => $field,
            ]);
            throw new \RuntimeException(sprintf('Aggregate %s repository row %d is missing field %s.', $query, $index, $field));
        }

        $value = trim((string) $row[$field]);
        if ('' === $value) {
            $this->logger->error('Analytics infra repository row contains an empty required string field.', [
                'query' => $query,
                'row_index' => $index,
                'field' => $field,
            ]);
            throw new \RuntimeException(sprintf('Aggregate %s repository row %d contains an empty field %s.', $query, $index, $field));
        }

        return $value;
    }

    /**
     * @param array<string,mixed> $row
     */
    private function readRequiredInt(array $row, string $field, string $query, int $index): int
    {
        if (!array_key_exists($field, $row) || (!is_scalar($row[$field]) && null !== $row[$field])) {
            $this->logger->error('Analytics infra repository row is missing a required integer field.', [
                'query' => $query,
                'row_index' => $index,
                'field' => $field,
            ]);
            throw new \RuntimeException(sprintf('Aggregate %s repository row %d is missing field %s.', $query, $index, $field));
        }

        return (int) $row[$field];
    }

    private function normalizeKey(string $value, string $field): string
    {
        $normalized = trim($value);

        if ('' === $normalized) {
            throw new \InvalidArgumentException(sprintf('%s must be a non-empty string.', $field));
        }

        return $normalized;
    }

    private function normalizePositiveInteger(int $value, string $field): int
    {
        if ($value <= 0) {
            throw new \InvalidArgumentException(sprintf('%s must be a positive integer.', $field));
        }

        return $value;
    }

    /**
     * @param list<string> $steps
     *
     * @return list<string>
     */
    private function normalizeStepList(array $steps): array
    {
        $normalized = array_values(array_unique(array_map(
            static function (string $step): string {
                $value = trim($step);
                if ('' === $value) {
                    throw new \InvalidArgumentException('steps must contain only non-empty values.');
                }

                return $value;
            },
            $steps,
        )));

        if (count($normalized) < 2) {
            throw new \InvalidArgumentException('steps must contain at least two unique values.');
        }

        return array_slice($normalized, 0, 4);
    }

    private function assertOrderedDateRange(\DateTimeImmutable $from, \DateTimeImmutable $to): void
    {
        if ($from > $to) {
            throw new \InvalidArgumentException('from must be earlier than or equal to to.');
        }
    }
}
