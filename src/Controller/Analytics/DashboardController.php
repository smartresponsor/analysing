<?php

declare(strict_types=1);

namespace App\Controller\Analytics;

use App\ControllerInterface\Analytics\DashboardControllerInterface;
use App\DTO\Analytics\KpiRequest;
use App\ServiceInterface\Analytics\DashboardServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class DashboardController implements DashboardControllerInterface
{
    private const COMPONENT = 'analytics';
    private const MAX_QUERY_VALUE_LENGTH = 255;

    public function __construct(
        private readonly DashboardServiceInterface $svc,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function kpi(Request $req): JsonResponse
    {
        return $this->runOperation('kpi', function () use ($req): array {
            $dto = $this->buildRequest($req);

            return $this->svc->kpi($dto);
        });
    }

    public function timeseries(Request $req): JsonResponse
    {
        return $this->runOperation('timeseries', function () use ($req): array {
            $dto = $this->buildRequest($req);

            return $this->svc->timeseries($dto);
        });
    }

    public function topVendors(Request $req): JsonResponse
    {
        return $this->runOperation('top_vendors', function () use ($req): array {
            $dto = $this->buildRequest($req);

            return $this->svc->byVendor($dto->currency, $dto->from, $dto->to);
        });
    }

    private function runOperation(string $operation, callable $callback): JsonResponse
    {
        $startedAt = microtime(true);

        try {
            $result = $callback();
            $this->logger->info('Dashboard controller operation completed.', [
                'operation' => $operation,
                'component' => self::COMPONENT,
                'result_count' => is_countable($result) ? count($result) : null,
                'duration_ms' => $this->durationMs($startedAt),
            ]);

            return new JsonResponse($result);
        } catch (BadRequestHttpException $exception) {
            return $this->invalidDashboardRequestResponse($operation, $exception, $startedAt);
        } catch (\RuntimeException $exception) {
            return $this->dashboardUnavailableResponse($operation, $exception, $startedAt);
        }
    }

    private function buildRequest(Request $req): KpiRequest
    {
        $vendorId = $this->parseVendorId($req->query->get('vendorId'));
        $currency = $this->parseCurrency($req->query->get('currency'));
        $from = $this->parseOptionalDate($req->query->get('from'), 'from');
        $to = $this->parseOptionalDate($req->query->get('to'), 'to');
        $this->assertRange($from, $to);

        return new KpiRequest(
            vendorId: $vendorId,
            currency: $currency,
            from: $from,
            to: $to,
        );
    }

    private function invalidDashboardRequestResponse(string $operation, BadRequestHttpException $exception, float $startedAt): JsonResponse
    {
        $this->logger->warning('Dashboard controller rejected request.', [
            'operation' => $operation,
            'component' => self::COMPONENT,
            'duration_ms' => $this->durationMs($startedAt),
            'exception' => $exception,
        ]);

        return new JsonResponse([
            'error' => 'Invalid dashboard request.',
            'operation' => $operation,
            'component' => self::COMPONENT,
            'time' => (new \DateTimeImmutable())->format(DATE_ATOM),
        ], Response::HTTP_BAD_REQUEST);
    }

    private function dashboardUnavailableResponse(string $operation, \RuntimeException $exception, float $startedAt): JsonResponse
    {
        $this->logger->error('Dashboard controller operation failed.', [
            'operation' => $operation,
            'component' => self::COMPONENT,
            'duration_ms' => $this->durationMs($startedAt),
            'exception' => $exception,
        ]);

        return new JsonResponse([
            'error' => 'Dashboard data unavailable.',
            'operation' => $operation,
            'component' => self::COMPONENT,
            'time' => (new \DateTimeImmutable())->format(DATE_ATOM),
        ], Response::HTTP_SERVICE_UNAVAILABLE);
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
        } catch (\Exception $exception) {
            throw new BadRequestHttpException(sprintf('Query parameter "%s" must be a valid date/time string.', $field), $exception);
        }
    }

    private function assertRange(?string $from, ?string $to): void
    {
        if (null === $from || null === $to) {
            return;
        }

        if (new \DateTimeImmutable($from) > new \DateTimeImmutable($to)) {
            throw new BadRequestHttpException('Query parameter "from" must be earlier than or equal to "to".');
        }
    }

    private function durationMs(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }
}
