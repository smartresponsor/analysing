<?php

declare(strict_types=1);

namespace App\Analysing\Service\Http;

use Symfony\Component\HttpFoundation\Request;

final class JsonRequestBodyDecoder
{
    private const int MAX_JSON_BYTES = 1048576;

    /**
     * @return array<string,mixed>
     */
    public function decode(Request $request): array
    {
        $content = trim($request->getContent());
        if ('' === $content) {
            return [];
        }

        if (strlen($content) > self::MAX_JSON_BYTES) {
            throw new \InvalidArgumentException('JSON payload is too large.');
        }

        try {
            $payload = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \InvalidArgumentException('Invalid JSON payload.', 0, $exception);
        }

        if (!is_array($payload)) {
            throw new \InvalidArgumentException('JSON payload must decode to an object or array.');
        }

        return $payload;
    }
}
