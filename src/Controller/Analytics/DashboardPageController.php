<?php

declare(strict_types=1);

namespace App\Controller\Analytics;

use App\ControllerInterface\Analytics\DashboardPageControllerInterface;
use App\Service\Analytics\DashboardHtmlRenderer;
use App\Service\Analytics\DashboardRequestFactory;
use App\Service\Http\AnalyticsErrorResponseFactory;
use App\Service\Http\AnalyticsSuccessResponseFactory;
use App\ServiceInterface\Analytics\DashboardServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class DashboardPageController implements DashboardPageControllerInterface
{
    private const string COMPONENT = 'analytics';
    private const string OPERATION = 'dashboard_page';

    public function __construct(
        private readonly DashboardServiceInterface $svc,
        private readonly LoggerInterface $logger,
        private readonly DashboardHtmlRenderer $htmlRenderer,
        private readonly AnalyticsSuccessResponseFactory $successResponses,
        private readonly AnalyticsErrorResponseFactory $errorResponses,
        private readonly ?DashboardRequestFactory $requestFactory = null,
    ) {
    }

    public function index(Request $req): Response
    {
        $startedAt = microtime(true);
        $jsonResponseRequested = $this->wantsJson($req);

        try {
            $dto = ($this->requestFactory ?? new DashboardRequestFactory())->fromRequest($req);

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
            return $this->invalidDashboardRequestResponse($exception, $startedAt, $jsonResponseRequested);
        } catch (\RuntimeException $exception) {
            return $this->dashboardUnavailableResponse($exception, $startedAt, $jsonResponseRequested);
        }

        $this->logger->info('Dashboard page controller completed.', [
            'component' => self::COMPONENT,
            'operation' => self::OPERATION,
            'duration_ms' => $this->durationMs($startedAt),
            'kpi_items' => count($payload['kpi']),
            'series_rows' => count($payload['series']),
            'top_rows' => count($payload['top']),
            'format' => $jsonResponseRequested ? 'json' : 'html',
        ]);

        if ($jsonResponseRequested) {
            return $this->successResponses->create(self::OPERATION, $payload, $startedAt);
        }

        return new Response(
            $this->htmlRenderer->renderDashboard($payload['kpi'], $payload['series'], $payload['top'], $payload['params']),
            Response::HTTP_OK,
            ['Content-Type' => 'text/html; charset=UTF-8']
        );
    }

    private function wantsJson(Request $request): bool
    {
        $format = strtolower(trim((string) $request->query->get('format', '')));
        if ('json' === $format) {
            return true;
        }

        $accept = strtolower(trim((string) $request->headers->get('Accept', '')));

        return '' !== $accept && str_contains($accept, 'application/json');
    }

    private function invalidDashboardRequestResponse(BadRequestHttpException $exception, float $startedAt, bool $jsonResponseRequested): Response
    {
        $this->logger->warning('Dashboard page controller rejected request.', [
            'component' => self::COMPONENT,
            'operation' => self::OPERATION,
            'duration_ms' => $this->durationMs($startedAt),
            'exception' => $exception,
            'format' => $jsonResponseRequested ? 'json' : 'html',
        ]);

        if ($jsonResponseRequested) {
            return $this->errorResponses->create(
                self::OPERATION,
                'Invalid dashboard request.',
                'analytics.dashboard.invalid_request',
                Response::HTTP_BAD_REQUEST,
                $startedAt,
            );
        }

        return new Response(
            $this->htmlRenderer->renderError('Invalid dashboard request', 'The dashboard query parameters are invalid for the current request.', Response::HTTP_BAD_REQUEST),
            Response::HTTP_BAD_REQUEST,
            ['Content-Type' => 'text/html; charset=UTF-8']
        );
    }

    private function dashboardUnavailableResponse(\RuntimeException $exception, float $startedAt, bool $jsonResponseRequested): Response
    {
        $this->logger->error('Dashboard page controller operation failed.', [
            'component' => self::COMPONENT,
            'operation' => self::OPERATION,
            'duration_ms' => $this->durationMs($startedAt),
            'exception' => $exception,
            'format' => $jsonResponseRequested ? 'json' : 'html',
        ]);

        if ($jsonResponseRequested) {
            return $this->errorResponses->create(
                self::OPERATION,
                'Dashboard data unavailable.',
                'analytics.dashboard.unavailable',
                Response::HTTP_SERVICE_UNAVAILABLE,
                $startedAt,
                true,
            );
        }

        return new Response(
            $this->htmlRenderer->renderError('Dashboard unavailable', 'The analytics dashboard data is currently unavailable.', Response::HTTP_SERVICE_UNAVAILABLE),
            Response::HTTP_SERVICE_UNAVAILABLE,
            ['Content-Type' => 'text/html; charset=UTF-8']
        );
    }

    private function durationMs(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }
}
