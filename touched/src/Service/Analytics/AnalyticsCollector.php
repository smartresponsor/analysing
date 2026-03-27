<?php
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

        $normalizedDimensions = $this->normalizeDimensions($dimensions);

        try {
            $snapshot = new MetricSnapshot($normalizedMetric, $value, $from, $to, $normalizedDimensions);
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

    /**
     * @param array<string,mixed>|null $dimensions
     * @return array<string,scalar|null>|null
     */
    private function normalizeDimensions(?array $dimensions): ?array
    {
        if ($dimensions === null) {
            return null;
        }

        $normalized = [];
        foreach ($dimensions as $key => $value) {
            $normalizedKey = trim((string) $key);
            if ($normalizedKey === '') {
                throw new InvalidArgumentException('Metric snapshot dimensions must not contain empty keys.');
            }

            if (!is_scalar($value) && $value !== null) {
                throw new InvalidArgumentException(sprintf('Metric snapshot dimension %s must be scalar.', $normalizedKey));
            }

            $normalized[$normalizedKey] = $value;
        }

        return $normalized;
    }

}
