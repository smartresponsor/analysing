<?php

declare(strict_types=1);

namespace App\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Loads the Symfony-native service export for the Analysing RC component.
 */
final class AnalysingExtension extends Extension
{
    /**
     * @param array<int, array<string, mixed>> $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        unset($configs);

        $configDirectory = __DIR__.'/../../config/component';
        $servicesFile = $configDirectory.'/services.yaml';

        if (!is_file($servicesFile)) {
            return;
        }

        $loader = new YamlFileLoader($container, new FileLocator($configDirectory));
        $loader->load('services.yaml');
    }
}
