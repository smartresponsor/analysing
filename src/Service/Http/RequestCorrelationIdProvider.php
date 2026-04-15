<?php

declare(strict_types=1);

namespace App\Service\Http;

use App\ServiceInterface\Http\RequestCorrelationIdProviderInterface;
use Random\RandomException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class RequestCorrelationIdProvider implements RequestCorrelationIdProviderInterface
{
    private const string ATTRIBUTE = '_analytics_correlation_id';
    private const string HEADER = 'X-Correlation-ID';
    private const int MAX_LENGTH = 128;

    private ?string $fallbackCorrelationId = null;

    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function initialize(Request $request): string
    {
        $existing = $request->attributes->get(self::ATTRIBUTE);
        if (is_string($existing) && '' !== $existing) {
            return $existing;
        }

        $incoming = trim((string) $request->headers->get(self::HEADER, ''));
        $correlationId = $this->isValid($incoming) ? $incoming : $this->generate();
        $request->attributes->set(self::ATTRIBUTE, $correlationId);

        return $correlationId;
    }

    public function current(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request instanceof Request) {
            return $this->initialize($request);
        }

        if (null === $this->fallbackCorrelationId) {
            $this->fallbackCorrelationId = $this->generate();
        }

        return $this->fallbackCorrelationId;
    }

    private function isValid(string $value): bool
    {
        return '' !== $value
            && strlen($value) <= self::MAX_LENGTH
            && 1 === preg_match('/^[A-Za-z0-9._:-]+$/', $value);
    }

    private function generate(): string
    {
        try {
            return 'corr-'.bin2hex(random_bytes(8));
        } catch (RandomException) {
            return 'corr-'.substr(hash('sha256', uniqid('analytics-correlation-', true)), 0, 16);
        }
    }
}
