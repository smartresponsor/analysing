<?php

declare(strict_types=1);

namespace App\Service\Analytics;

use App\ServiceInterface\Analytics\BackfillServiceInterface;
use Psr\Log\LoggerInterface;

final class BackfillService implements BackfillServiceInterface
{
    private const int MAX_RANGE_SECONDS = 31536000;

    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function run(int $fromTs, int $toTs): int
    {
        if ($fromTs <= 0 || $toTs <= 0) {
            $this->logger->warning('Analytics backfill rejected because timestamps must be positive.', [
                'from_ts' => $fromTs,
                'to_ts' => $toTs,
            ]);
            throw new \InvalidArgumentException('Backfill timestamps must be positive.');
        }

        if ($fromTs > $toTs) {
            $this->logger->warning('Analytics backfill rejected because the range is inverted.', [
                'from_ts' => $fromTs,
                'to_ts' => $toTs,
            ]);
            throw new \InvalidArgumentException('Backfill range is invalid.');
        }

        if (($toTs - $fromTs) > self::MAX_RANGE_SECONDS) {
            $this->logger->warning('Analytics backfill rejected because the range is too large.', [
                'from_ts' => $fromTs,
                'to_ts' => $toTs,
                'range_seconds' => $toTs - $fromTs,
                'max_range_seconds' => self::MAX_RANGE_SECONDS,
            ]);
            throw new \InvalidArgumentException('Backfill range exceeds the maximum allowed size.');
        }

        $from = $this->timestampToDate($fromTs, 'fromTs');
        $to = $this->timestampToDate($toTs, 'toTs');
        $minutes = intdiv($toTs - $fromTs, 60);

        $this->logger->info('Analytics backfill window calculated.', [
            'from_ts' => $fromTs,
            'to_ts' => $toTs,
            'from' => $from->format(DATE_ATOM),
            'to' => $to->format(DATE_ATOM),
            'minutes' => $minutes,
            'seconds' => $toTs - $fromTs,
        ]);

        return max(0, $minutes);
    }

    private function timestampToDate(int $timestamp, string $field): \DateTimeImmutable
    {
        try {
            return (new \DateTimeImmutable('@'.$timestamp))->setTimezone(new \DateTimeZone('UTC'));
        } catch (\Throwable $exception) {
            $this->logger->warning('Analytics backfill rejected because timestamp could not be converted to a date.', [
                'field' => $field,
                'timestamp' => $timestamp,
                'exception' => $exception,
            ]);
            throw new \InvalidArgumentException(sprintf('%s is not a valid unix timestamp.', $field), 0, $exception);
        }
    }
}
