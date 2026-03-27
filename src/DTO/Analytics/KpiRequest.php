<?php

declare(strict_types=1);

namespace App\DTO\Analytics;

final class KpiRequest
{
    public readonly ?int $vendorId;
    public readonly ?string $currency;
    public readonly ?string $from;
    public readonly ?string $to;

    public function __construct(
        ?int $vendorId = null,
        ?string $currency = null,
        ?string $from = null,
        ?string $to = null,
    ) {
        $this->vendorId = $this->normalizeVendorId($vendorId);
        $this->currency = $this->normalizeCurrency($currency);
        $this->from = $this->normalizeDate($from, 'from');
        $this->to = $this->normalizeDate($to, 'to');
        $this->assertOrderedRange($this->from, $this->to);
    }

    private function normalizeVendorId(?int $vendorId): ?int
    {
        if (null === $vendorId) {
            return null;
        }

        if ($vendorId <= 0) {
            throw new \InvalidArgumentException('KPI request vendorId must be a positive integer.');
        }

        return $vendorId;
    }

    private function normalizeCurrency(?string $currency): ?string
    {
        if (null === $currency) {
            return null;
        }

        $normalized = strtoupper(trim($currency));
        if ('' === $normalized) {
            return null;
        }

        if (1 !== preg_match('/^[A-Z]{3}$/', $normalized)) {
            throw new \InvalidArgumentException('KPI request currency must be a 3-letter ISO code.');
        }

        return $normalized;
    }

    private function normalizeDate(?string $value, string $field): ?string
    {
        if (null === $value) {
            return null;
        }

        $normalized = trim($value);
        if ('' === $normalized) {
            return null;
        }

        try {
            return (new \DateTimeImmutable($normalized))->format('Y-m-d H:i:s');
        } catch (\Throwable $exception) {
            throw new \InvalidArgumentException(sprintf('KPI request %s must be a valid date/time string.', $field), 0, $exception);
        }
    }

    private function assertOrderedRange(?string $from, ?string $to): void
    {
        if (null === $from || null === $to) {
            return;
        }

        if (new \DateTimeImmutable($from) > new \DateTimeImmutable($to)) {
            throw new \InvalidArgumentException('KPI request "from" must be earlier than or equal to "to".');
        }
    }
}
