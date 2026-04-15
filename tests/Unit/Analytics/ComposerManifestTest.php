<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\Tests\Support\JsonPayloadAssertionsTrait;
use PHPUnit\Framework\TestCase;

final class ComposerManifestTest extends TestCase
{
    use JsonPayloadAssertionsTrait;

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

        self::assertSame(['App\\' => 'src/'], $composer['autoload']['psr-4']);
        self::assertSame(['App\\Tests\\' => 'tests/'], $composer['autoload-dev']['psr-4']);
        self::assertSame('project', $composer['type']);
        self::assertSame('dev@highhopesamerica.com', $composer['authors'][0]['email']);
        self::assertArrayHasKey('symfony/framework-bundle', $composer['require']);
        self::assertArrayHasKey('doctrine/dbal', $composer['require']);
        self::assertArrayHasKey('phpunit/phpunit', $composer['require-dev']);
    }
}
