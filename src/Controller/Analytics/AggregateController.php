<?php

declare(strict_types=1);

namespace App\Controller\Analytics;

use App\ControllerInterface\Analytics\AggregateControllerInterface;
use App\Service\Http\AnalyticsErrorResponseFactory;
use App\Service\Http\AnalyticsSuccessResponseFactory;
use App\ServiceInterface\Analytics\AggregateServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class AggregateController implements AggregateControllerInterface
{
    private const COMPONENT = 'analytics';
    private const MAX_JSON_BYTES = 1048576;
    private const MAX_LIST_ITEMS = 64;
    private const MAX_STRING_LENGTH = 255;

    public function __construct(
        private readonly AggregateServiceInterface $service,
        private readonly LoggerInterface $logger,
        private readonly AnalyticsSuccessResponseFactory $successResponses,
        private readonly AnalyticsErrorResponseFactory $errorResponses,
    ) {
    }

    public function funnel(Request $request): JsonResponse
    {
        return $this->runOperation('funnel', function () use ($request): array {
            $body = $this->decodeBody($request);
            $app = $this->requireNonEmptyString($body, 'app');
            $env = $this->requireNonEmptyString($body, 'env');
            $steps = $this->requireStringList($body, 'steps');
            $from = $this->parseDateTime($body, 'from');
            $to = $this->parseDateTime($body, 'to');
            $this->assertRange($from, $to, 'from', 'to');

            return $this->service->computeFunnel($app, $env, $steps, $from, $to);
        });
    }

    public function retention(Request $request): JsonResponse
    {
        return $this->runOperation('retention', function () use ($request): array {
            $body = $this->decodeBody($request);
            $app = $this->requireNonEmptyString($body, 'app');
            $env = $this->requireNonEmptyString($body, 'env');
            $cohort = $this->parseDateTime($body, 'cohort');
            $days = $this->requirePositiveInt($body, 'days');

            return $this->service->computeRetention($app, $env, $cohort, $days);
        });
    }

    public function path(Request $request): JsonResponse
    {
        return $this->runOperation('path', function () use ($request): array {
            $body = $this->decodeBody($request);
            $app = $this->requireNonEmptyString($body, 'app');
            $env = $this->requireNonEmptyString($body, 'env');
            $day = $this->parseDateTime($body, 'day');
            $top = $this->requirePositiveInt($body, 'top');

            return $this->service->computePath($app, $env, $day, $top);
        });
    }

    private function runOperation(string $operation, callable $callback): JsonResponse
    {
        $startedAt = microtime(true);

        try {
            $result = $callback();

            $this->logger->info('Analytics aggregate operation completed.', [
                'operation' => $operation,
                'component' => self::COMPONENT,
                'result_count' => is_countable($result) ? count($result) : null,
                'duration_ms' => $this->durationMs($startedAt),
            ]);

            return $this->successResponses->create($operation, $result, $startedAt);
        } catch (BadRequestHttpException|\InvalidArgumentException $exception) {
            $this->logger->warning('Analytics aggregate request rejected.', [
                'operation' => $operation,
                'component' => self::COMPONENT,
                'duration_ms' => $this->durationMs($startedAt),
                'exception' => $exception,
            ]);

            return $this->errorResponses->create(
                $operation,
                'Invalid aggregate request.',
                'analytics.aggregate.invalid_request',
                Response::HTTP_BAD_REQUEST,
                $startedAt,
            );
        } catch (\RuntimeException $exception) {
            $this->logger->error('Analytics aggregate operation failed.', [
                'operation' => $operation,
                'component' => self::COMPONENT,
                'duration_ms' => $this->durationMs($startedAt),
                'exception' => $exception,
            ]);

            return $this->errorResponses->create(
                $operation,
                'Aggregate data unavailable.',
                'analytics.aggregate.unavailable',
                Response::HTTP_SERVICE_UNAVAILABLE,
                $startedAt,
                true,
            );
        }
    }

    /**
     * @return array<string,mixed>
     */
    private function decodeBody(Request $request): array
    {
        $content = trim($request->getContent());
        if ('' === $content) {
            return [];
        }

        if (strlen($content) > self::MAX_JSON_BYTES) {
            throw new BadRequestHttpException('JSON payload is too large.');
        }

        try {
            $payload = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new BadRequestHttpException('Invalid JSON payload.', $exception);
        }

        if (!is_array($payload)) {
            throw new BadRequestHttpException('JSON payload must decode to an object or array.');
        }

        return $payload;
    }

    /**
     * @param array<string,mixed> $body
     */
    private function requireNonEmptyString(array $body, string $field): string
    {
        $value = $body[$field] ?? null;
        if (!is_string($value)) {
            throw new BadRequestHttpException(sprintf('Field "%s" must be a non-empty string.', $field));
        }

        $normalized = trim($value);
        if ('' === $normalized) {
            throw new BadRequestHttpException(sprintf('Field "%s" must be a non-empty string.', $field));
        }

        if (strlen($normalized) > self::MAX_STRING_LENGTH) {
            throw new BadRequestHttpException(sprintf('Field "%s" is too long.', $field));
        }

        return $normalized;
    }

    /**
     * @param array<string,mixed> $body
     *
     * @return list<string>
     */
    private function requireStringList(array $body, string $field): array
    {
        $value = $body[$field] ?? null;
        if (!is_array($value) || [] === $value) {
            throw new BadRequestHttpException(sprintf('Field "%s" must be a non-empty list of strings.', $field));
        }

        if (count($value) > self::MAX_LIST_ITEMS) {
            throw new BadRequestHttpException(sprintf('Field "%s" contains too many items.', $field));
        }

        $items = [];
        foreach ($value as $index => $item) {
            if (!is_string($item)) {
                throw new BadRequestHttpException(sprintf('Field "%s" item %d must be a string.', $field, $index));
            }

            $normalized = trim($item);
            if ('' === $normalized) {
                throw new BadRequestHttpException(sprintf('Field "%s" item %d must not be empty.', $field, $index));
            }

            if (strlen($normalized) > self::MAX_STRING_LENGTH) {
                throw new BadRequestHttpException(sprintf('Field "%s" item %d is too long.', $field, $index));
            }

            $items[] = $normalized;
        }

        return $items;
    }

    /**
     * @param array<string,mixed> $body
     */
    private function requirePositiveInt(array $body, string $field): int
    {
        $value = $body[$field] ?? null;
        if (is_int($value) && $value > 0) {
            return $value;
        }

        throw new BadRequestHttpException(sprintf('Field "%s" must be a positive integer.', $field));
    }

    /**
     * @param array<string,mixed> $body
     */
    private function parseDateTime(array $body, string $field): \DateTimeImmutable
    {
        $value = $body[$field] ?? null;
        if (!is_string($value) || '' === trim($value)) {
            throw new BadRequestHttpException(sprintf('Field "%s" must be a valid date/time string.', $field));
        }

        if (strlen($value) > self::MAX_STRING_LENGTH) {
            throw new BadRequestHttpException(sprintf('Field "%s" is too long.', $field));
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception $exception) {
            throw new BadRequestHttpException(sprintf('Field "%s" must be a valid date/time string.', $field), $exception);
        }
    }

    private function assertRange(\DateTimeImmutable $from, \DateTimeImmutable $to, string $fromField, string $toField): void
    {
        if ($from > $to) {
            throw new BadRequestHttpException(sprintf('Field "%s" must be earlier than or equal to "%s".', $fromField, $toField));
        }
    }

    private function durationMs(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }
}
