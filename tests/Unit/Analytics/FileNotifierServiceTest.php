<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Service\Analytics\FileNotifier;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class FileNotifierServiceTest extends TestCase
{
    public function testSendAppendsPayloadToResolvedPath(): void
    {
        $path = sys_get_temp_dir().'/analytics-file-notifier-'.uniqid('', true).'.log';
        @unlink($path);

        $service = new FileNotifier(new NullLogger(), $path);

        self::assertTrue($service->send('orders.alert', ['status' => 'ok']));
        self::assertFileExists($path);
        self::assertStringContainsString('orders.alert', (string) file_get_contents($path));

        @unlink($path);
    }

    public function testSendRejectsEmptyEndpoint(): void
    {
        $service = new FileNotifier(new NullLogger());

        self::assertFalse($service->send('   ', ['status' => 'ok']));
    }
}
