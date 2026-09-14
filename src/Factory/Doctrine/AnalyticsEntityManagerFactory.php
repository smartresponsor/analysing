<?php

declare(strict_types=1);

namespace App\Analysing\Factory\Doctrine;

use App\Analysing\FactoryInterface\Doctrine\AnalyticsEntityManagerFactoryInterface;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\DefaultSchemaManagerFactory;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;

final class AnalyticsEntityManagerFactory implements AnalyticsEntityManagerFactoryInterface
{
    public static function create(): EntityManagerInterface
    {
        $url = getenv('ANALYTICS_DATABASE_URL');
        if (is_string($url) && '' !== trim($url)) {
            $normalizedUrl = trim($url);
            $scheme = strtolower((string) parse_url($normalizedUrl, PHP_URL_SCHEME));
            if (in_array($scheme, ['sqlite', 'pdo-sqlite'], true) && (!extension_loaded('pdo') || !extension_loaded('pdo_sqlite'))) {
                throw new \RuntimeException('Analytics storage requires the pdo_sqlite extension or a working ANALYTICS_DATABASE_URL driver.');
            }

            $connection = DriverManager::getConnection([
                'url' => $normalizedUrl,
            ], self::dbalConfiguration());

            $config = ORMSetup::createAttributeMetadataConfig(
                paths: [dirname(__DIR__, 3).'/src/Entity'],
                isDevMode: self::isDebug(),
            );
            $config->enableNativeLazyObjects(true);

            return new EntityManager($connection, $config);
        }

        if (!extension_loaded('pdo') || !extension_loaded('pdo_sqlite')) {
            throw new \RuntimeException('Analytics storage requires the pdo_sqlite extension or a working ANALYTICS_DATABASE_URL driver.');
        }

        $path = dirname(__DIR__, 3).'/var/analytics.sqlite';
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException('Analytics storage directory could not be created: '.$directory);
        }

        $connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'path' => $path,
        ], self::dbalConfiguration());

        $config = ORMSetup::createAttributeMetadataConfig(
            paths: [dirname(__DIR__, 3).'/src/Entity'],
            isDevMode: self::isDebug(),
        );
        $config->enableNativeLazyObjects(true);

        return new EntityManager($connection, $config);
    }

    private static function dbalConfiguration(): Configuration
    {
        $configuration = new Configuration();
        $configuration->setSchemaManagerFactory(new DefaultSchemaManagerFactory());

        return $configuration;
    }

    private static function isDebug(): bool
    {
        $value = getenv('APP_DEBUG');

        return false !== $value && '0' !== $value && '' !== $value;
    }
}
