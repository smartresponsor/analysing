<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Support;

use Symfony\Component\HttpFoundation\Response;

trait AnalyticsJsonPayloadAssertionsTrait
{
    /**
     * @return array<string,mixed>
     */
    private function decodeJsonResponse(Response $response): array
    {
        $content = $response->getContent();
        if (false === $content) {
            throw new \RuntimeException('Response content is unavailable.');
        }

        $payload = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($payload)) {
            throw new \RuntimeException('JSON response payload must decode to an array.');
        }

        return $this->normalizeAssociativeArray($payload);
    }

    /**
     * @param array<string,mixed> $payload
     *
     * @return array<string,mixed>
     */
    private function requireArrayAt(array $payload, string $key): array
    {
        $value = $payload[$key] ?? null;
        if (!is_array($value)) {
            throw new \RuntimeException(sprintf('Payload key "%s" must be an array.', $key));
        }

        return $this->normalizeAssociativeArray($value);
    }

    /**
     * @return array<string,mixed>
     */
    private function decodeJsonFile(string $path): array
    {
        $content = file_get_contents($path);
        if (false === $content) {
            throw new \RuntimeException('Unable to read JSON file: '.$path);
        }

        $payload = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($payload)) {
            throw new \RuntimeException('JSON file payload must decode to an array.');
        }

        return $this->normalizeAssociativeArray($payload);
    }

    /**
     * @param array<array-key, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function normalizeAssociativeArray(array $payload): array
    {
        $normalized = [];
        foreach ($payload as $key => $value) {
            if (!is_string($key)) {
                throw new \RuntimeException('JSON payload must decode to an object with string keys.');
            }

            $normalized[$key] = $value;
        }

        return $normalized;
    }
}
