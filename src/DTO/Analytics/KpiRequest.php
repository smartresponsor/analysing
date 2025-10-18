<?php
declare(strict_types=1);

namespace App\DTO\Analytics;

final class KpiRequest
{
    public function __construct(
        public readonly ?int $vendorId = null,
        public readonly ?string $currency = null,
        public readonly ?string $from = null,
        public readonly ?string $to = null
    ) {}
}
