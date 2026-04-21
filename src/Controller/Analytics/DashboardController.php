<?php

declare(strict_types=1);

namespace App\Analysing\Controller\Analytics;

use App\Analysing\ControllerInterface\Analytics\DashboardControllerInterface;
use App\Analysing\Service\Analytics\DashboardRequestFactory;
use App\Analysing\Service\Http\AnalyticsErrorResponseFactory;
use App\Analysing\Service\Http\AnalyticsSuccessResponseFactory;
use App\Analysing\ServiceInterface\Analytics\DashboardServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class DashboardController implements DashboardControllerInterface
{
    private const string COMPONENT = 'analytics';

    public function __construct(
        private readonly DashboardServiceInterface $svc,
        private readonly LoggerInterface $logger,
        private readonly AnalyticsSuccessResponseFactory $successResponses,
        private readonly AnalyticsErrorResponseFactory $errorResponses,
        private readonly ?DashboardRequestFactory $requestFactory = null,
    ) {
    }

    public function kpi(Request $req): JsonResponse
    {
        return $this->runOperation('kpi', function () use ($req): array {
            $dto = ($this->requestFactory ?? new DashboardRequestFactory())->fromRequest($req);

            return $this->svc->kpi($dto);
        });
    }

    public function timeseries(Request $req): JsonResponse
    {
        return $this->runOperation('timeseries', function () use ($req): array {
            $dto = ($this->requestFactory ?? new DashboardRequestFactory())->fromRequest($req);

            return $this->svc->timeseries($dto);
        });
    }

    public function topVendors(Request $req): JsonResponse
    {
        return $this->runOperation('top_vendors', function () use ($req): array {
            $dto = ($this->requestFactory ?? new DashboardRequestFactory())->fromRequest($req);

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

            return $this->successResponses->create($operation, $result, $startedAt);
        } catch (BadRequestHttpException $exception) {
            return $this->invalidDashboardRequestResponse($operation, $exception, $startedAt);
        } catch (\RuntimeException $exception) {
            return $this->dashboardUnavailableResponse($operation, $exception, $startedAt);
        }
    }

    private function invalidDashboardRequestResponse(string $operation, BadRequestHttpException $exception, float $startedAt): JsonResponse
    {
        $this->logger->warning('Dashboard controller rejected request.', [
            'operation' => $operation,
            'component' => self::COMPONENT,
            'duration_ms' => $this->durationMs($startedAt),
            'exception' => $exception,
        ]);

        return $this->errorResponses->create(
            $operation,
            'Invalid dashboard request.',
            'analytics.dashboard.invalid_request',
            Response::HTTP_BAD_REQUEST,
            $startedAt,
        );
    }

    private function dashboardUnavailableResponse(string $operation, \RuntimeException $exception, float $startedAt): JsonResponse
    {
        $this->logger->error('Dashboard controller operation failed.', [
            'operation' => $operation,
            'component' => self::COMPONENT,
            'duration_ms' => $this->durationMs($startedAt),
            'exception' => $exception,
        ]);

        return $this->errorResponses->create(
            $operation,
            'Dashboard data unavailable.',
            'analytics.dashboard.unavailable',
            Response::HTTP_SERVICE_UNAVAILABLE,
            $startedAt,
            true,
        );
    }

    private function durationMs(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }
}
