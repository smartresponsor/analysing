<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\Controller\Analytics\FlagController;
use App\DomainInterface\Analytics\FlagInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

final class FlagControllerTest extends TestCase
{
    public function testEvaluateReturnsDomainPayload(): void
    {
        $domain = $this->createMock(FlagInterface::class);
        $domain->method('evaluate')->willReturn([
            'flag_key' => 'checkout',
            'user_id' => 'u1',
            'enabled' => true,
            'bucket' => 42,
            'reason' => 'rollout',
            'rollout' => 100,
        ]);

        $controller = new FlagController($domain, $this->createMock(LoggerInterface::class));
        $response = $controller->evaluate(new Request([], [], [], [], [], [], json_encode(['flag_key' => 'checkout', 'user_id' => 'u1'], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($payload['enabled']);
        self::assertSame('checkout', $payload['flag_key']);
    }
}
