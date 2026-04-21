<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Entity\Alerts\AlertRule;
use App\Analysing\Service\Alerts\NotificationDispatcher;
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

        self::assertSame('sales-high', $rule->getCode());
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
