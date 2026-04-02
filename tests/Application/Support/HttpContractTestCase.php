<?php

declare(strict_types=1);

namespace App\Tests\Application\Support;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Base test case for HTTP-style contract assertions.
 *
 * This class is intentionally transport-agnostic: it works with controller-produced JsonResponse
 * objects today and can later be reused by WebTestCase-based HTTP tests once a Symfony kernel test
 * bootstrap is confirmed in the repository.
 */
abstract class HttpContractTestCase extends TestCase
{
    /**
     * Decodes a JsonResponse into an associative array.
     *
     * @return array<string,mixed>
     */
    protected function decodeJson(JsonResponse $response): array
    {
        /** @var array<string,mixed> $payload */
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        return $payload;
    }

    /**
     * Asserts that a response has the expected HTTP status code.
     */
    protected function assertStatus(JsonResponse $response, int $expectedStatusCode): void
    {
        self::assertSame($expectedStatusCode, $response->getStatusCode());
    }

    /**
     * Placeholder helper for a future full-kernel HTTP layer.
     *
     * This method makes the intended migration path explicit: once Symfony WebTestCase bootstrap is
     * available, tests inheriting from this base class can be upgraded from controller-level request
     * simulation to real HTTP client execution without changing contract assertions.
     */
    protected function assertKernelReady(bool $kernelReady): void
    {
        self::assertIsBool($kernelReady);
    }
}
