<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Controller\AnalyticsFlagController;
use App\Analysing\ServiceInterface\AnalyticsFlagInterface;
use App\Analysing\Tests\Support\AnalyticsHttpFactoriesTrait;
use App\Analysing\Tests\Support\AnalyticsJsonPayloadAssertionsTrait;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

final class FlagControllerTest extends TestCase
{
    use AnalyticsHttpFactoriesTrait;
    use AnalyticsJsonPayloadAssertionsTrait;

    public function testEvaluateReturnsDomainPayload(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode(['flag_key' => 'checkout', 'user_id' => 'u1'], JSON_THROW_ON_ERROR));
        $domain = $this->createMock(AnalyticsFlagInterface::class);
        $domain->method('evaluate')->willReturn([
            'flag_key' => 'checkout',
            'user_id' => 'u1',
            'enabled' => true,
            'bucket' => 42,
            'reason' => 'rollout',
            'rollout' => 100,
        ]);

        $controller = new AnalyticsFlagController(
            $domain,
            $this->createMock(LoggerInterface::class),
            $this->createSuccessFactory($request),
            $this->createErrorFactory($request),
        );
        $response = $controller->evaluate($request);
        $payload = $this->decodeJsonResponse($response);

        self::assertSame(200, $response->getStatusCode());
        $data = $this->requireArrayAt($payload, 'data');
        self::assertTrue((bool) $data['enabled']);
        self::assertSame('checkout', $data['flag_key']);
    }
}
