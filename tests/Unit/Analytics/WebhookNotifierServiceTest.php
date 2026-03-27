<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\Service\Analytics\WebhookNotifier;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class WebhookNotifierServiceTest extends TestCase
{
    public function testSendSpoolsPayloadToTemporaryDirectory(): void
    {
        $dir = sys_get_temp_dir().'/analytics_webhook';
        if (is_dir($dir)) {
            foreach (glob($dir.'/*.json') ?: [] as $file) {
                @unlink($file);
            }
        }

        $service = new WebhookNotifier(new NullLogger());

        self::assertTrue($service->send('https://example.test/hook', ['status' => 'ok']));
        self::assertNotEmpty(glob($dir.'/*.json') ?: []);
    }

    public function testSendRejectsEmptyEndpoint(): void
    {
        $service = new WebhookNotifier(new NullLogger());

        self::assertFalse($service->send('', ['status' => 'ok']));
    }
}
