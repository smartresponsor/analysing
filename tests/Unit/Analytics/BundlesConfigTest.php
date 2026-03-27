<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;

final class BundlesConfigTest extends TestCase
{
    public function testBundlesConfigRegistersFrameworkBundle(): void
    {
        $bundles = require __DIR__.'/../../../config/bundles.php';

        self::assertArrayHasKey(FrameworkBundle::class, $bundles);
        self::assertSame(['all' => true], $bundles[FrameworkBundle::class]);
    }
}
