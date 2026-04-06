<?php

declare(strict_types=1);

namespace App\Service\Analytics;

use App\Entity\Analytics\MetricSnapshot;
use App\ServiceInterface\Analytics\AnalyticsCollectorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final class AnalyticsCollector implements AnalyticsCollectorInterface
{
    private const int MAX_DIMENSIONS = 64;
    private const int MAX_DIMENSION_KEY_LENGTH = 128;
    private const int MAX_DIMENSION_VALUE_LENGTH = 512;
    private const int MAX_METRIC_LENGTH = 128;
    private const int MAX_RANGE_SECONDS = 31536000;

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
        if ('' === $normalizedMetric) {
            throw new \InvalidArgumentException('Metric name must not be empty.');
        }

        if (strlen($normalizedMetric) > self::MAX_METRIC_LENGTH) {
            throw new \InvalidArgumentException('Metric name exceeds the maximum supported length.');
        }

        if (!is_finite($value)) {
            throw new \InvalidArgumentException('Metric value must be finite.');
        }

        if ($from > $to) {
            throw new \InvalidArgumentException('Metric snapshot start must be earlier than or equal to end.');
        }

        if (($to->getTimestamp() - $from->getTimestamp()) > self::MAX_RANGE_SECONDS) {
            throw new \InvalidArgumentException('Metric snapshot range exceeds the maximum supported duration.');
        }

        $normalizedDimensions = $this->normalizeDimensions($dimensions);

        try {
            $snapshot = new MetricSnapshot($normalizedMetric, $value, $from, $to, $normalizedDimensions);
            $this->em->persist($snapshot);
            $this->em->flush();
            $this->logger->info('Analytics collector persisted metric snapshot.', [
                'metric' => $normalizedMetric,
                'from' => $from->format(DATE_ATOM),
                'to' => $to->format(DATE_ATOM),
                'dimensions_count' => null !== $normalizedDimensions ? count($normalizedDimensions) : 0,
            ]);
        } catch (\Throwable $exception) {
            $this->logger->error('Analytics collector could not persist metric snapshot.', [
                'exception' => $exception,
                'metric' => $normalizedMetric,
                'value' => $value,
                'from' => $from->format(DATE_ATOM),
                'to' => $to->format(DATE_ATOM),
            ]);

            throw new \RuntimeException('Analytics collector could not persist metric snapshot.', 0, $exception);
        }
    }

    /**
     * @param array<string,mixed>|null $dimensions
     *
     * @return array<string,scalar|null>|null
     */
    private function normalizeDimensions(?array $dimensions): ?array
    {
        if (null === $dimensions) {
            return null;
        }

        if (count($dimensions) > self::MAX_DIMENSIONS) {
            throw new \InvalidArgumentException('Metric snapshot dimensions exceed the maximum supported count.');
        }

        $normalized = [];
        foreach ($dimensions as $key => $value) {
            $normalizedKey = trim((string) $key);
            if ('' === $normalizedKey) {
                throw new \InvalidArgumentException('Metric snapshot dimensions must not contain empty keys.');
            }

            if (strlen($normalizedKey) > self::MAX_DIMENSION_KEY_LENGTH) {
                throw new \InvalidArgumentException(sprintf('Metric snapshot dimension key %s is too long.', $normalizedKey));
            }

            if (!is_scalar($value) && null !== $value) {
                throw new \InvalidArgumentException(sprintf('Metric snapshot dimension %s must be scalar.', $normalizedKey));
            }

            if (is_string($value) && strlen($value) > self::MAX_DIMENSION_VALUE_LENGTH) {
                throw new \InvalidArgumentException(sprintf('Metric snapshot dimension %s value is too long.', $normalizedKey));
            }

            $normalized[$normalizedKey] = $value;
        }

        return $normalized;
    }
}
