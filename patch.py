from pathlib import Path
base=Path('/tmp/analytics45')
files={}
files['src/Service/Analytics/AccessGuard.php'] = '''<?php
declare(strict_types=1);

namespace App\Service\Analytics;

use App\ServiceInterface\Analytics\AccessGuardInterface;
use Psr\Log\LoggerInterface;

final class AccessGuard implements AccessGuardInterface
{
    /** @var list<string> */
    private array $allow;

    public function __construct(
        private readonly LoggerInterface $logger,
        array $allow = [],
    ) {
        $this->allow = $this->normalizeAllowList($allow);
    }

    public function allow(string $subject): bool
    {
        $normalizedSubject = trim($subject);
        if ($normalizedSubject === '') {
            $this->logger->warning('Analytics access guard rejected an empty subject.');

            return false;
        }

        if ($this->allow === []) {
            return true;
        }

        return in_array($normalizedSubject, $this->allow, true);
    }

    /**
     * @param array<mixed> $allow
     * @return list<string>
     */
    private function normalizeAllowList(array $allow): array
    {
        $normalized = [];

        foreach ($allow as $entry) {
            if (!is_scalar($entry)) {
                $this->logger->warning('Analytics access guard ignored a non-scalar allow-list entry.', [
                    'entry_type' => get_debug_type($entry),
                ]);
                continue;
            }

            $value = trim((string) $entry);
            if ($value === '') {
                continue;
            }

            $normalized[] = $value;
        }

        return array_values(array_unique($normalized));
    }
}
'''
files['src/Service/Analytics/WindowQuery.php'] = '''<?php
declare(strict_types=1);

namespace App\Service\Analytics;

use App\ServiceInterface\Analytics\WindowQueryInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

final class WindowQuery implements WindowQueryInterface
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function window(array $rows, int $size): array
    {
        if ($size <= 0) {
            $this->logger->warning('Analytics window query rejected a non-positive window size.', [
                'size' => $size,
            ]);

            throw new InvalidArgumentException('Window size must be positive.');
        }

        $out = [];
        $count = count($rows);

        for ($offset = 0; $offset < $count; $offset += $size) {
            $out[] = array_slice($rows, $offset, $size);
        }

        return $out;
    }
}
'''
files['src/Service/Analytics/Transformer.php'] = '''<?php
declare(strict_types=1);

namespace App\Service\Analytics;

use App\ServiceInterface\Analytics\TransformerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

final class Transformer implements TransformerInterface
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function map(array $rows, callable $fn): array
    {
        $mapped = [];

        foreach ($rows as $index => $row) {
            try {
                $mapped[] = $fn($row);
            } catch (Throwable $exception) {
                $this->logger->error('Analytics transformer callable failed for a row.', [
                    'exception' => $exception,
                    'row_index' => $index,
                ]);

                throw new RuntimeException('Analytics transformer failed for row ' . $index . '.', 0, $exception);
            }
        }

        return $mapped;
    }
}
'''
files['src/Service/Analytics/ReportBundle.php'] = '''<?php
declare(strict_types=1);

namespace App\Service\Analytics;

use App\ServiceInterface\Analytics\ReportBundleInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

final class ReportBundle implements ReportBundleInterface
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function pack(array $datasets): array
    {
        $manifest = [];

        foreach ($datasets as $name => $rows) {
            $datasetName = trim((string) $name);
            if ($datasetName === '') {
                $this->logger->warning('Analytics report bundle rejected a dataset with an empty name.');
                throw new InvalidArgumentException('Dataset name must not be empty.');
            }

            if (!is_array($rows)) {
                $this->logger->warning('Analytics report bundle rejected a dataset with a non-array payload.', [
                    'dataset' => $datasetName,
                    'payload_type' => get_debug_type($rows),
                ]);
                throw new InvalidArgumentException('Dataset rows must be an array.');
            }

            $manifest[$datasetName] = ['rows' => count($rows)];
        }

        return $manifest;
    }
}
'''
files['src/Service/Analytics/RollupService.php'] = '''<?php
declare(strict_types=1);

namespace App\Service\Analytics;

use App\ServiceInterface\Analytics\RollupServiceInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

final class RollupService implements RollupServiceInterface
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function sum(array $rows, string $field): float|int
    {
        $normalizedField = trim($field);
        if ($normalizedField === '') {
            $this->logger->warning('Analytics rollup rejected an empty field name.');
            throw new InvalidArgumentException('Rollup field must not be empty.');
        }

        $total = 0.0;

        foreach ($rows as $index => $row) {
            if (!is_array($row)) {
                $this->logger->warning('Analytics rollup ignored a non-array row.', [
                    'row_index' => $index,
                    'row_type' => get_debug_type($row),
                ]);
                continue;
            }

            $value = $row[$normalizedField] ?? 0;
            if (is_array($value) || is_object($value)) {
                $this->logger->warning('Analytics rollup ignored a non-scalar field value.', [
                    'row_index' => $index,
                    'field' => $normalizedField,
                    'value_type' => get_debug_type($value),
                ]);
                continue;
            }

            $total += (float) $value;
        }

        return $total;
    }
}
'''
files['src/Service/Analytics/SegmentationService.php'] = '''<?php
declare(strict_types=1);

namespace App\Service\Analytics;

use App\ServiceInterface\Analytics\SegmentationServiceInterface;
use App\ValueObject\Analytics\Dimension;
use App\ValueObject\Analytics\Segment;
use Psr\Log\LoggerInterface;

final class SegmentationService implements SegmentationServiceInterface
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function apply(array $rows, Dimension $dim, Segment $seg): array
    {
        $key = $dim->name();
        $code = $seg->code();
        $matched = [];

        foreach ($rows as $index => $row) {
            if (!is_array($row)) {
                $this->logger->warning('Analytics segmentation ignored a non-array row.', [
                    'row_index' => $index,
                    'row_type' => get_debug_type($row),
                ]);
                continue;
            }

            if (($row[$key] ?? null) === $code) {
                $matched[] = $row;
            }
        }

        return array_values($matched);
    }
}
'''
files['src/Service/Analytics/AggregateService.php'] = '''<?php declare(strict_types=1);
/*
 * Marketing America Corp. Oleksandr Tishchenko
 * Comments in English only. Singular naming.
 */
namespace App\Service\Analytics;

use App\RepositoryInterface\Analytics\InfraRepositoryInterface;
use App\ServiceInterface\Analytics\AggregateServiceInterface;
use DateTimeImmutable;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

final class AggregateService implements AggregateServiceInterface
{
    public function __construct(
        private readonly InfraRepositoryInterface $repo,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function computeFunnel(string $app, string $env, array $steps, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $app = $this->normalizeLabel($app, 'app');
        $env = $this->normalizeLabel($env, 'env');
        $steps = $this->normalizeSteps($steps);
        $this->assertOrderedRange($from, $to, 'from', 'to');

        try {
            return $this->repo->fetchFunnel($app, $env, $steps, $from, $to);
        } catch (Throwable $exception) {
            $this->logger->error('Analytics aggregate funnel query failed.', [
                'exception' => $exception,
                'app' => $app,
                'env' => $env,
                'steps' => $steps,
                'from' => $from->format(DATE_ATOM),
                'to' => $to->format(DATE_ATOM),
            ]);

            throw new RuntimeException('Aggregate funnel data unavailable.', 0, $exception);
        }
    }

    public function computeRetention(string $app, string $env, DateTimeImmutable $cohort, int $days): array
    {
        $app = $this->normalizeLabel($app, 'app');
        $env = $this->normalizeLabel($env, 'env');

        if ($days <= 0) {
            throw new InvalidArgumentException('days must be a positive integer.');
        }

        try {
            return $this->repo->fetchRetention($app, $env, $cohort, $days);
        } catch (Throwable $exception) {
            $this->logger->error('Analytics aggregate retention query failed.', [
                'exception' => $exception,
                'app' => $app,
                'env' => $env,
                'cohort' => $cohort->format('Y-m-d'),
                'days' => $days,
            ]);

            throw new RuntimeException('Aggregate retention data unavailable.', 0, $exception);
        }
    }

    public function computePath(string $app, string $env, DateTimeImmutable $day, int $top): array
    {
        $app = $this->normalizeLabel($app, 'app');
        $env = $this->normalizeLabel($env, 'env');

        if ($top <= 0) {
            throw new InvalidArgumentException('top must be a positive integer.');
        }

        try {
            return $this->repo->fetchPath($app, $env, $day, $top);
        } catch (Throwable $exception) {
            $this->logger->error('Analytics aggregate path query failed.', [
                'exception' => $exception,
                'app' => $app,
                'env' => $env,
                'day' => $day->format('Y-m-d'),
                'top' => $top,
            ]);

            throw new RuntimeException('Aggregate path data unavailable.', 0, $exception);
        }
    }

    private function normalizeLabel(string $value, string $field): string
    {
        $normalized = trim($value);

        if ($normalized === '') {
            throw new InvalidArgumentException(sprintf('%s must be a non-empty string.', $field));
        }

        return $normalized;
    }

    private function normalizeSteps(array $steps): array
    {
        $normalized = [];

        foreach ($steps as $step) {
            if (!is_scalar($step)) {
                throw new InvalidArgumentException('steps must contain only scalar values.');
            }

            $value = trim((string) $step);

            if ($value === '') {
                throw new InvalidArgumentException('steps must contain only non-empty values.');
            }

            $normalized[] = $value;
        }

        $normalized = array_values(array_unique($normalized));

        if (count($normalized) < 2) {
            throw new InvalidArgumentException('steps must contain at least two unique values.');
        }

        return array_slice($normalized, 0, 4);
    }

    private function assertOrderedRange(DateTimeImmutable $from, DateTimeImmutable $to, string $fromField, string $toField): void
    {
        if ($from > $to) {
            throw new InvalidArgumentException(sprintf('%s must be earlier than or equal to %s.', $fromField, $toField));
        }
    }
}
'''
files['src/Repository/Analytics/InfraRepository.php'] = '''<?php declare(strict_types=1);
/*
 * Marketing America Corp. Oleksandr Tishchenko
 * Comments in English only. Singular naming.
 */
namespace App\Repository\Analytics;

use App\RepositoryInterface\Analytics\InfraRepositoryInterface;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

final class InfraRepository implements InfraRepositoryInterface
{
    public function __construct(
        private readonly Connection $connection,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function fetchFunnel(string $app, string $env, array $steps, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $app = $this->normalizeKey($app, 'app');
        $env = $this->normalizeKey($env, 'env');
        $steps = $this->normalizeStepList($steps);
        $this->assertOrderedDateRange($from, $to, 'from', 'to');

        $conditions = [
            'app = :app',
            'env = :env',
            'day BETWEEN :from AND :to',
            'step_1 = :step1',
            'step_2 = :step2',
        ];
        $params = [
            'app' => $app,
            'env' => $env,
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
            'step1' => $steps[0],
            'step2' => $steps[1],
        ];

        if (isset($steps[2])) {
            $conditions[] = 'step_3 = :step3';
            $params['step3'] = $steps[2];
        }
        if (isset($steps[3])) {
            $conditions[] = 'step_4 = :step4';
            $params['step4'] = $steps[3];
        }

        $sql = sprintf(
            'SELECT day, user_count FROM aggregate_funnel_daily WHERE %s ORDER BY day ASC',
            implode(' AND ', $conditions),
        );

        try {
            return $this->connection->fetchAllAssociative($sql, $params);
        } catch (Throwable $exception) {
            $this->logger->error('Analytics infra repository funnel query failed.', [
                'exception' => $exception,
                'app' => $app,
                'env' => $env,
                'steps' => $steps,
                'from' => $params['from'],
                'to' => $params['to'],
            ]);

            throw new RuntimeException('Aggregate funnel repository query failed.', 0, $exception);
        }
    }

    public function fetchRetention(string $app, string $env, DateTimeImmutable $cohort, int $days): array
    {
        $app = $this->normalizeKey($app, 'app');
        $env = $this->normalizeKey($env, 'env');
        $days = $this->normalizePositiveInteger($days, 'days');

        $sql = 'SELECT day_offset, active_user FROM retention_cohort_daily WHERE app = :app AND env = :env AND cohort = :cohort AND day_offset <= :days ORDER BY day_offset';
        $params = [
            'app' => $app,
            'env' => $env,
            'cohort' => $cohort->format('Y-m-d'),
            'days' => $days,
        ];

        try {
            return $this->connection->fetchAllAssociative($sql, $params, [
                'days' => ParameterType::INTEGER,
            ]);
        } catch (Throwable $exception) {
            $this->logger->error('Analytics infra repository retention query failed.', [
                'exception' => $exception,
                'app' => $app,
                'env' => $env,
                'cohort' => $params['cohort'],
                'days' => $days,
            ]);

            throw new RuntimeException('Aggregate retention repository query failed.', 0, $exception);
        }
    }

    public function fetchPath(string $app, string $env, DateTimeImmutable $day, int $top): array
    {
        $app = $this->normalizeKey($app, 'app');
        $env = $this->normalizeKey($env, 'env');
        $top = $this->normalizePositiveInteger($top, 'top');

        $sql = 'SELECT from_event, to_event, transition_count FROM path_transition_daily WHERE app = :app AND env = :env AND day = :day ORDER BY transition_count DESC LIMIT :top';
        $params = [
            'app' => $app,
            'env' => $env,
            'day' => $day->format('Y-m-d'),
            'top' => $top,
        ];

        try {
            return $this->connection->fetchAllAssociative($sql, $params, [
                'top' => ParameterType::INTEGER,
            ]);
        } catch (Throwable $exception) {
            $this->logger->error('Analytics infra repository path query failed.', [
                'exception' => $exception,
                'app' => $app,
                'env' => $env,
                'day' => $params['day'],
                'top' => $top,
            ]);

            throw new RuntimeException('Aggregate path repository query failed.', 0, $exception);
        }
    }

    public function upsertExperimentMetricDaily(string $experimentKey, string $variantKey, DateTimeImmutable $day, int $exposure, int $conversion, float $valueSum): void
    {
        $experimentKey = $this->normalizeKey($experimentKey, 'experimentKey');
        $variantKey = $this->normalizeKey($variantKey, 'variantKey');

        if ($exposure < 0) {
            throw new InvalidArgumentException('exposure must be zero or greater.');
        }

        if ($conversion < 0) {
            throw new InvalidArgumentException('conversion must be zero or greater.');
        }

        $sql = 'INSERT INTO experiment_metric_daily (day, experiment_key, variant_key, exposure, conversion, value_sum)
                VALUES (:day, :experiment_key, :variant_key, :exposure, :conversion, :value_sum)
                ON CONFLICT (day, experiment_key, variant_key)
                DO UPDATE SET exposure = EXCLUDED.exposure, conversion = EXCLUDED.conversion, value_sum = EXCLUDED.value_sum';
        $params = [
            'day' => $day->format('Y-m-d'),
            'experiment_key' => $experimentKey,
            'variant_key' => $variantKey,
            'exposure' => $exposure,
            'conversion' => $conversion,
            'value_sum' => $valueSum,
        ];

        try {
            $this->connection->executeStatement($sql, $params, [
                'exposure' => ParameterType::INTEGER,
                'conversion' => ParameterType::INTEGER,
            ]);
        } catch (Throwable $exception) {
            $this->logger->error('Analytics infra repository experiment upsert failed.', [
                'exception' => $exception,
                'day' => $params['day'],
                'experiment_key' => $experimentKey,
                'variant_key' => $variantKey,
                'exposure' => $exposure,
                'conversion' => $conversion,
            ]);

            throw new RuntimeException('Experiment metric upsert failed.', 0, $exception);
        }
    }

    private function normalizeKey(string $value, string $field): string
    {
        $normalized = trim($value);

        if ($normalized === '') {
            throw new InvalidArgumentException(sprintf('%s must be a non-empty string.', $field));
        }

        return $normalized;
    }

    private function normalizePositiveInteger(int $value, string $field): int
    {
        if ($value <= 0) {
            throw new InvalidArgumentException(sprintf('%s must be a positive integer.', $field));
        }

        return $value;
    }

    private function normalizeStepList(array $steps): array
    {
        $normalized = [];

        foreach ($steps as $step) {
            if (!is_scalar($step)) {
                throw new InvalidArgumentException('steps must contain only scalar values.');
            }

            $value = trim((string) $step);

            if ($value === '') {
                throw new InvalidArgumentException('steps must contain only non-empty values.');
            }

            $normalized[] = $value;
        }

        $normalized = array_values(array_unique($normalized));

        if (count($normalized) < 2) {
            throw new InvalidArgumentException('steps must contain at least two unique values.');
        }

        return array_slice($normalized, 0, 4);
    }

    private function assertOrderedDateRange(DateTimeImmutable $from, DateTimeImmutable $to, string $fromField, string $toField): void
    {
        if ($from > $to) {
            throw new InvalidArgumentException(sprintf('%s must be earlier than or equal to %s.', $fromField, $toField));
        }
    }
}
'''
files['src/Service/Analytics/AnalyticsCollector.php'] = '''<?php
declare(strict_types=1);

namespace App\Service\Analytics;

use App\Entity\Analytics\MetricSnapshot;
use App\ServiceInterface\Analytics\AnalyticsCollectorInterface;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

final class AnalyticsCollector implements AnalyticsCollectorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function record(
        string $metric,
        float $value,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
        ?array $dimensions = null,
    ): void {
        $normalizedMetric = trim($metric);
        if ($normalizedMetric === '') {
            throw new InvalidArgumentException('Metric name must not be empty.');
        }

        if ($from > $to) {
            throw new InvalidArgumentException('Metric snapshot start must be earlier than or equal to end.');
        }

        try {
            $snapshot = new MetricSnapshot($normalizedMetric, $value, $from, $to, $dimensions);
            $this->em->persist($snapshot);
            $this->em->flush();
        } catch (Throwable $exception) {
            $this->logger->error('Analytics collector could not persist metric snapshot.', [
                'exception' => $exception,
                'metric' => $normalizedMetric,
                'value' => $value,
                'from' => $from->format(DATE_ATOM),
                'to' => $to->format(DATE_ATOM),
            ]);

            throw new RuntimeException('Analytics collector could not persist metric snapshot.', 0, $exception);
        }
    }
}
'''
for path, content in files.items():
    p=base/path
    p.write_text(content)
