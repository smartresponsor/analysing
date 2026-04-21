<?php

declare(strict_types=1);

namespace App\Analysing\Service\Analytics;

use App\Analysing\ServiceInterface\Analytics\TokenServiceInterface;
use Psr\Log\LoggerInterface;

final class TokenService implements TokenServiceInterface
{
    private const int MAX_TTL = 604800;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly string $salt = 'analytics-default-salt',
    ) {
    }

    public function issue(array $scope, int $ttl = 3600): string
    {
        if ($ttl <= 0 || $ttl > self::MAX_TTL) {
            throw new \InvalidArgumentException('Analytics token TTL is out of range.');
        }

        $now = time();
        $payload = [
            'scope' => $this->normalizeValue($scope),
            'iat' => $now,
            'exp' => $now + $ttl,
        ];

        try {
            $json = json_encode($payload, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            $this->logger->error('Analytics token payload encode failed.', ['exception' => $exception]);

            throw new \InvalidArgumentException('Analytics token payload could not be encoded.', 0, $exception);
        }
        $encoded = self::base64UrlEncode($json);
        $signature = self::base64UrlEncode(hash_hmac('sha256', $encoded, $this->salt, true));

        return $encoded.'.'.$signature;
    }

    public function verify(string $token): array
    {
        $normalized = trim($token);
        if ('' === $normalized || !str_contains($normalized, '.')) {
            return [];
        }

        [$payloadEncoded, $signatureEncoded] = explode('.', $normalized, 2);
        if ('' === $payloadEncoded || '' === $signatureEncoded) {
            return [];
        }

        $expected = self::base64UrlEncode(hash_hmac('sha256', $payloadEncoded, $this->salt, true));
        if (!hash_equals($expected, $signatureEncoded)) {
            return [];
        }

        $decoded = self::base64UrlDecode($payloadEncoded);
        if (false === $decoded) {
            return [];
        }

        try {
            $payload = json_decode($decoded, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            $this->logger->warning('Analytics token payload decode failed.', ['exception' => $exception]);

            return [];
        }

        return is_array($payload) ? $payload : [];
    }

    private function normalizeValue(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        if (!array_is_list($value)) {
            ksort($value);
        }

        foreach ($value as $key => $item) {
            $value[$key] = $this->normalizeValue($item);
        }

        return $value;
    }

    private static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $value): string|false
    {
        $padding = (4 - strlen($value) % 4) % 4;

        return base64_decode(strtr($value.str_repeat('=', $padding), '-_', '+/'), true);
    }
}
