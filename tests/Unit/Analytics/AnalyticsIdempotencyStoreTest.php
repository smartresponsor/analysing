<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Service\Http\AnalyticsIdempotencyStore;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\JsonResponse;

final class AnalyticsIdempotencyStoreTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir().'/analytics-idempotency-store-'.bin2hex(random_bytes(6));
    }

    protected function tearDown(): void
    {
        if (!is_dir($this->directory)) {
            return;
        }

        $entries = scandir($this->directory);
        if (false !== $entries) {
            foreach ($entries as $entry) {
                if ('.' === $entry || '..' === $entry) {
                    continue;
                }

                @unlink($this->directory.'/'.$entry);
            }
        }

        @rmdir($this->directory);
    }

    public function testStoreCanReplayCompletedResponse(): void
    {
        $store = new AnalyticsIdempotencyStore(new NullLogger(), $this->directory, 60);

        $started = $store->begin('analytics_flag_evaluate', 'acme', 'idem-1', 'fingerprint-a');
        self::assertSame('new', $started['status']);

        $store->finalize('analytics_flag_evaluate', 'acme', 'idem-1', 'fingerprint-a', new JsonResponse(['ok' => true], 200));

        $replayed = $store->begin('analytics_flag_evaluate', 'acme', 'idem-1', 'fingerprint-a');
        self::assertSame('replay', $replayed['status']);

        $response = $store->buildReplayResponse($replayed['record'] ?? []);
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('{"ok":true}', $response->getContent());
    }

    public function testStoreRejectsFingerprintConflict(): void
    {
        $store = new AnalyticsIdempotencyStore(new NullLogger(), $this->directory, 60);
        $store->begin('analytics_flag_evaluate', 'acme', 'idem-2', 'fingerprint-a');

        $conflict = $store->begin('analytics_flag_evaluate', 'acme', 'idem-2', 'fingerprint-b');
        self::assertSame('conflict', $conflict['status']);
    }
}
