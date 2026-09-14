<?php

declare(strict_types=1);

namespace App\Analysing\Factory;

use App\Analysing\DTO\AnalyticsKpiRequestDTO;
use App\Analysing\FactoryInterface\AnalyticsDashboardRequestFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class AnalyticsDashboardRequestFactory implements AnalyticsDashboardRequestFactoryInterface
{
    private const int MAX_QUERY_VALUE_LENGTH = 255;

    public function fromRequest(Request $request): AnalyticsKpiRequestDTO
    {
        $from = $this->parseOptionalDate($request->query->get('from'), 'from');
        $to = $this->parseOptionalDate($request->query->get('to'), 'to');
        $this->assertRange($from, $to);

        return new AnalyticsKpiRequestDTO(
            vendorId: $this->parseVendorId($request->query->get('vendorId')),
            currency: $this->parseCurrency($request->query->get('currency')),
            from: $from,
            to: $to,
        );
    }

    private function parseVendorId(mixed $value): ?int
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if (is_string($value) && strlen($value) > self::MAX_QUERY_VALUE_LENGTH) {
            throw new BadRequestHttpException('Query parameter "vendorId" is too long.');
        }

        if (is_string($value) && 1 === preg_match('/^\d+$/', $value)) {
            $vendorId = (int) $value;
            if ($vendorId > 0) {
                return $vendorId;
            }
        }

        throw new BadRequestHttpException('Query parameter "vendorId" must be a positive integer.');
    }

    private function parseCurrency(mixed $value): ?string
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if (!is_string($value)) {
            throw new BadRequestHttpException('Query parameter "currency" must be a string.');
        }

        if (strlen($value) > self::MAX_QUERY_VALUE_LENGTH) {
            throw new BadRequestHttpException('Query parameter "currency" is too long.');
        }

        $currency = strtoupper(trim($value));
        if ('' === $currency || 1 !== preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new BadRequestHttpException('Query parameter "currency" must be a 3-letter ISO code.');
        }

        return $currency;
    }

    private function parseOptionalDate(mixed $value, string $field): ?string
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if (!is_string($value)) {
            throw new BadRequestHttpException(sprintf('Query parameter "%s" must be a valid date/time string.', $field));
        }

        if (strlen($value) > self::MAX_QUERY_VALUE_LENGTH) {
            throw new BadRequestHttpException(sprintf('Query parameter "%s" is too long.', $field));
        }

        try {
            return (new \DateTimeImmutable($value))->format('Y-m-d H:i:s');
        } catch (\Throwable $exception) {
            throw new BadRequestHttpException(sprintf('Query parameter "%s" must be a valid date/time string.', $field), $exception);
        }
    }

    private function assertRange(?string $from, ?string $to): void
    {
        if (null === $from || null === $to) {
            return;
        }

        if ($from > $to) {
            throw new BadRequestHttpException('Query parameter "from" must be earlier than or equal to "to".');
        }
    }
}
