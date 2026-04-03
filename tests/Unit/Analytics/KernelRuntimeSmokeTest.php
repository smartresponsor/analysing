<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\Kernel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class KernelRuntimeSmokeTest extends TestCase
{
    public function testStatusRouteCanBootAndHandleRequest(): void
    {
        require_once __DIR__.'/../../../vendor/autoload.php';
        require_once __DIR__.'/../../../config/bootstrap.php';

        $kernel = new Kernel('test', true);
        $response = $kernel->handle(Request::create('/status', 'GET'));

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('analytics', $response->getContent() ?: '');

        $kernel->shutdown();
    }
}
