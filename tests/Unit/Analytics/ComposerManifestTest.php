<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\Tests\Support\AnalyticsJsonPayloadAssertionsTrait;
use PHPUnit\Framework\TestCase;

final class ComposerManifestTest extends TestCase
{
    use AnalyticsJsonPayloadAssertionsTrait;

    public function testComposerManifestKeepsCanonicalAutoloadAndCorePackages(): void
    {
        $composer = $this->decodeJsonFile(__DIR__.'/../../../composer.json');
        self::assertIsArray($composer['autoload']);
        self::assertIsArray($composer['autoload-dev']);
        self::assertIsArray($composer['authors']);
        self::assertIsArray($composer['require']);
        self::assertIsArray($composer['require-dev']);
        self::assertIsArray($composer['autoload']['psr-4']);
        self::assertIsArray($composer['autoload-dev']['psr-4']);
        self::assertIsArray($composer['authors'][0]);

        self::assertSame(['App\Analysing\\' => 'src/'], $composer['autoload']['psr-4']);
        self::assertSame(['App\Analysing\\Tests\\' => 'tests/'], $composer['autoload-dev']['psr-4']);
        self::assertSame('library', $composer['type']);
        self::assertSame('dev@highhopesamerica.com', $composer['authors'][0]['email']);
        self::assertArrayHasKey('symfony/framework-bundle', $composer['require']);
        self::assertArrayHasKey('doctrine/dbal', $composer['require']);
        self::assertArrayHasKey('phpunit/phpunit', $composer['require-dev']);
    }
}
