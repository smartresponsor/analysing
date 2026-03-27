<?php

declare(strict_types=1);

namespace App\Controller\Analytics;

use App\ControllerInterface\Analytics\DashboardPageControllerInterface;
use App\DTO\Analytics\KpiRequest;
use App\ServiceInterface\Analytics\DashboardServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class DashboardPageController implements DashboardPageControllerInterface
{
    private const COMPONENT = 'analytics';
    private const MAX_QUERY_VALUE_LENGTH = 255;

    public function __construct(
        private readonly DashboardServiceInterface $svc,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function index(Request $req): Response
    {
        $startedAt = microtime(true);

        try {
            $dto = $this->buildRequest($req);

            $payload = [
                'kpi' => $this->svc->kpi($dto),
                'series' => $this->svc->timeseries($dto),
                'top' => $this->svc->byVendor($dto->currency, $dto->from, $dto->to),
                'params' => [
                    'vendorId' => $dto->vendorId,
                    'currency' => $dto->currency,
                    'from' => $dto->from,
                    'to' => $dto->to,
                ],
            ];
        } catch (BadRequestHttpException $exception) {
            return $this->invalidDashboardRequestResponse($exception, $startedAt);
        } catch (\RuntimeException $exception) {
            return $this->dashboardUnavailableResponse($exception, $startedAt);
        }

        try {
            $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            $this->logger->error('Unable to encode dashboard payload.', [
                'component' => self::COMPONENT,
                'duration_ms' => $this->durationMs($startedAt),
                'exception' => $exception,
            ]);

            return new Response(
                '{"error":"Dashboard payload unavailable.","component":"analytics","time":"'.(new \DateTimeImmutable())->format(DATE_ATOM).'"}',
                Response::HTTP_INTERNAL_SERVER_ERROR,
                ['Content-Type' => 'application/json']
            );
        }

        $this->logger->info('Dashboard page controller completed.', [
            'component' => self::COMPONENT,
            'duration_ms' => $this->durationMs($startedAt),
            'kpi_items' => is_countable($payload['kpi']) ? count($payload['kpi']) : null,
            'series_rows' => is_countable($payload['series']) ? count($payload['series']) : null,
            'top_rows' => is_countable($payload['top']) ? count($payload['top']) : null,
        ]);

        return new Response($json, Response::HTTP_OK, ['Content-Type' => 'application/json']);
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

    private function invalidDashboardRequestResponse(BadRequestHttpException $exception, float $startedAt): Response
    {
        $this->logger->warning('Dashboard page controller rejected request.', [
            'component' => self::COMPONENT,
            'duration_ms' => $this->durationMs($startedAt),
            'exception' => $exception,
        ]);

        return new Response(
            '{"error":"Invalid dashboard request.","component":"analytics","time":"'.(new \DateTimeImmutable())->format(DATE_ATOM).'"}',
            Response::HTTP_BAD_REQUEST,
            ['Content-Type' => 'application/json']
        );
    }

    private function dashboardUnavailableResponse(\RuntimeException $exception, float $startedAt): Response
    {
        $this->logger->error('Dashboard page controller operation failed.', [
            'component' => self::COMPONENT,
            'duration_ms' => $this->durationMs($startedAt),
            'exception' => $exception,
        ]);

        return new Response(
            '{"error":"Dashboard data unavailable.","component":"analytics","time":"'.(new \DateTimeImmutable())->format(DATE_ATOM).'"}',
            Response::HTTP_SERVICE_UNAVAILABLE,
            ['Content-Type' => 'application/json']
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
