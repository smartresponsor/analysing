<?php declare(strict_types=1);
namespace App\DTO\Analytics;
final class KpiRequest
{
    public function __construct(
        public readonly string $metric,
        public readonly \DateTimeImmutable $from,
        public readonly \DateTimeImmutable $to,
        public readonly ?array $dimensions = null
    ) {}
}
