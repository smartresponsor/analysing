<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\Entity\Alerts\AlertRule;
use App\Service\Alerts\NotificationDispatcher;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class NotificationDispatcherTest extends TestCase
{
    public function testDispatchAcceptsNormalizedChannelDefinitions(): void
    {
        $rule = new AlertRule('sales-high', 'Sales High', [
            'metric' => 'sales',
            'operator' => '>=',
            'value' => 10,
        ], [
            'email',
            ['type' => 'webhook', 'target' => 'https://example.com/hook'],
            ['type' => 'unsupported'],
        ]);

        $dispatcher = new NotificationDispatcher(new NullLogger());
        $dispatcher->dispatch($rule, 'Alert payload');

        self::assertTrue(true);
    }

    public function testDispatchRejectsEmptyMessage(): void
    {
        $rule = new AlertRule('sales-high', 'Sales High', [
            'metric' => 'sales',
            'operator' => '>=',
            'value' => 10,
        ], ['email']);

        $dispatcher = new NotificationDispatcher(new NullLogger());

        $this->expectException(\InvalidArgumentException::class);
        $dispatcher->dispatch($rule, '   ');
    }
}
