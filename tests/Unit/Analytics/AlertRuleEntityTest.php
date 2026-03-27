<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\Entity\Alerts\AlertRule;
use PHPUnit\Framework\TestCase;

final class AlertRuleEntityTest extends TestCase
{
    public function testConstructsWithNormalizedChannelsAndCondition(): void
    {
        $rule = new AlertRule(
            'order-drop',
            'Order drop',
            ['metric' => 'orders', 'threshold' => 10],
            ['email', ['type' => 'webhook', 'target' => 'https://example.test/hook']],
        );

        self::assertSame('order-drop', $rule->getCode());
        self::assertSame('Order drop', $rule->getName());
        self::assertSame('orders', $rule->getCondition()['metric']);
        self::assertCount(2, $rule->getChannels());
        self::assertTrue($rule->isActive());
    }

    public function testRejectsEmptyCondition(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new AlertRule('code', 'Name', []);
    }
}
