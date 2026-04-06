<?php

declare(strict_types=1);

namespace App\Service\Analytics;

use App\ServiceInterface\Analytics\TokenServiceInterface;
use Psr\Log\LoggerInterface;

final class TokenService implements TokenServiceInterface
{
    private const MAX_TTL = 604800;
    private const MAX_SCOPE_ENTRIES = 128;
    private const MAX_SCOPE_DEPTH = 5;
    private const MAX_KEY_LENGTH = 128;
    private const MAX_SCOPE_STRING_LENGTH = 2048;
    private const MAX_TOKEN_LENGTH = 32768;
    private const MAX_SIGNATURE_LENGTH = 128;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly string $salt = 'analytics-default-salt',
    ) {
    }

    public function issue(array $scope, int $ttl = 3600): string
    {
        if ($ttl <= 0) {
            $this->logger->warning('Analytics token issue rejected because ttl is not positive.', [
                'ttl' => $ttl,
            ]);

            throw new \InvalidArgumentException('Token ttl must be a positive integer.');
        }

        if ($ttl > self::MAX_TTL) {
            $this->logger->warning('Analytics token issue rejected because ttl exceeds the maximum allowed value.', [
                'ttl' => $ttl,
                'max_ttl' => self::MAX_TTL,
            ]);

            throw new \InvalidArgumentException('Token ttl exceeds the maximum allowed value.');
        }

        $normalizedScope = $this->normalizeScope($scope, 'scope');
        $issuedAt = $this->utcNow();
        $expiresAt = $issuedAt->modify(sprintf('+%d seconds', $ttl));
        $payload = json_encode([
            'scope' => $normalizedScope,
            'iat' => $issuedAt->getTimestamp(),
            'exp' => $expiresAt->getTimestamp(),
        ], JSON_THROW_ON_ERROR);

        $encodedPayload = $this->base64UrlEncode($payload);
        $signature = $this->sign($encodedPayload);
        $token = $encodedPayload.'.'.$signature;

        $this->logger->info('Analytics token issued.', [
            'ttl' => $ttl,
            'token_length' => strlen($token),
            'scope_entries' => count($normalizedScope),
        ]);

        return $token;
    }

    public function verify(string $token): array
    {
        $trimmedToken = trim($token);
        if ('' === $trimmedToken) {
            $this->logger->warning('Analytics token verification rejected because token is empty.');

            return [];
        }

        if (strlen($trimmedToken) > self::MAX_TOKEN_LENGTH) {
            $this->logger->warning('Analytics token verification rejected because token is too long.', [
                'token_length' => strlen($trimmedToken),
                'max_length' => self::MAX_TOKEN_LENGTH,
            ]);

            return [];
        }

        $parts = explode('.', $trimmedToken, 2);
        if (2 !== count($parts)) {
            $this->logger->warning('Analytics token verification rejected because token format is invalid.');

            return [];
        }

        [$encodedPayload, $signature] = $parts;
        if ('' === $encodedPayload || '' === $signature || strlen($signature) > self::MAX_SIGNATURE_LENGTH || !ctype_xdigit($signature)) {
            $this->logger->warning('Analytics token verification rejected because signature segment is invalid.', [
                'signature_length' => strlen($signature),
            ]);

            return [];
        }

        $expectedSignature = $this->sign($encodedPayload);
        if (!hash_equals($expectedSignature, $signature)) {
            $this->logger->warning('Analytics token verification rejected because signature verification failed.');

            return [];
        }

        $raw = $this->base64UrlDecode($encodedPayload);
        if (null === $raw) {
            $this->logger->warning('Analytics token verification rejected because payload encoding is invalid.');

            return [];
        }

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            $this->logger->warning('Analytics token verification rejected because payload JSON is invalid.', [
                'exception' => $exception,
            ]);

            return [];
        }

        if (!is_array($decoded)) {
            $this->logger->warning('Analytics token verification rejected because payload is not an object.', [
                'payload_type' => get_debug_type($decoded),
            ]);

            return [];
        }

        $exp = $decoded['exp'] ?? null;
        if (!is_int($exp)) {
            $this->logger->warning('Analytics token verification rejected because exp is missing or invalid.', [
                'exp' => $exp,
            ]);

            return [];
        }

        $iat = $decoded['iat'] ?? null;
        if (null !== $iat && !is_int($iat)) {
            $this->logger->warning('Analytics token verification rejected because iat is invalid.', [
                'iat' => $iat,
            ]);

            return [];
        }

        if (is_int($iat) && $iat > $exp) {
            $this->logger->warning('Analytics token verification rejected because iat is later than exp.', [
                'iat' => $iat,
                'exp' => $exp,
            ]);

            return [];
        }

        $nowTs = $this->utcNow()->getTimestamp();
        if (is_int($iat) && $iat > $nowTs + 300) {
            $this->logger->warning('Analytics token verification rejected because iat is unrealistically in the future.', [
                'iat' => $iat,
                'now' => $nowTs,
            ]);

            return [];
        }

        if (($exp - (is_int($iat) ? $iat : $nowTs)) > self::MAX_TTL) {
            $this->logger->warning('Analytics token verification rejected because token lifetime exceeds the maximum allowed value.', [
                'iat' => $iat,
                'exp' => $exp,
                'max_ttl' => self::MAX_TTL,
            ]);

            return [];
        }

        if ($exp < $nowTs) {
            $this->logger->info('Analytics token verification rejected because token is expired.', [
                'exp' => $exp,
            ]);

            return [];
        }

        $scope = $decoded['scope'] ?? [];
        if (!is_array($scope)) {
            $this->logger->warning('Analytics token verification rejected because scope is not an array.', [
                'scope_type' => get_debug_type($scope),
            ]);

            return [];
        }

        try {
            $decoded['scope'] = $this->normalizeScope($scope, 'scope');
        } catch (\InvalidArgumentException $exception) {
            $this->logger->warning('Analytics token verification rejected because scope shape is invalid.', [
                'exception' => $exception,
            ]);

            return [];
        }

        return $decoded;
    }

    /**
     * @param array<array-key,mixed> $scope
     *
     * @return array<string,mixed>
     */
    private function normalizeScope(array $scope, string $path, int $depth = 0): array
    {
        if ($depth > self::MAX_SCOPE_DEPTH) {
            throw new \InvalidArgumentException(sprintf('%s exceeds the maximum nesting depth.', $path));
        }

        if (count($scope) > self::MAX_SCOPE_ENTRIES) {
            throw new \InvalidArgumentException(sprintf('%s exceeds the maximum number of entries.', $path));
        }

        $normalized = [];

        foreach ($scope as $key => $value) {
            $normalizedKey = trim((string) $key);
            if ('' === $normalizedKey) {
                throw new \InvalidArgumentException(sprintf('%s must not contain empty keys.', $path));
            }

            if (strlen($normalizedKey) > self::MAX_KEY_LENGTH) {
                throw new \InvalidArgumentException(sprintf('%s key %s exceeds the maximum allowed length.', $path, $normalizedKey));
            }

            if (is_scalar($value) || null === $value) {
                if (is_string($value) && strlen($value) > self::MAX_SCOPE_STRING_LENGTH) {
                    throw new \InvalidArgumentException(sprintf('%s value for key %s exceeds the maximum allowed length.', $path, $normalizedKey));
                }
                $normalized[$normalizedKey] = $value;
                continue;
            }

            if (!is_array($value)) {
                throw new \InvalidArgumentException(sprintf('%s contains an unsupported value type for key %s.', $path, $normalizedKey));
            }

            $normalized[$normalizedKey] = $this->normalizeScope($value, $path.'.'.$normalizedKey, $depth + 1);
        }

        ksort($normalized);

        return $normalized;
    }

    private function sign(string $encodedPayload): string
    {
        return hash_hmac('sha256', $encodedPayload, $this->salt);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): ?string
    {
        if ('' === $value || preg_match('/^[A-Za-z0-9\-_]+$/', $value) !== 1) {
            return null;
        }

        $padding = strlen($value) % 4;
        if (0 !== $padding) {
            $value .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        return false === $decoded ? null : $decoded;
    }

    private function utcNow(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }
}
