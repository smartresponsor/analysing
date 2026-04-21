<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Tests\Support\TestKernel;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class KernelRuntimeSmokeTest extends TestCase
{
    #[RunInSeparateProcess]
    public function testStatusRouteCanBootAndHandleRequest(): void
    {
        require_once __DIR__.'/../../../vendor/autoload.php';
        require_once __DIR__.'/../../../config/bootstrap.php';

        $kernel = new TestKernel('test', true);
        $response = $kernel->handle(Request::create('/status', 'GET'));

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('analytics', $response->getContent() ?: '');

        $kernel->shutdown();
    }
}
