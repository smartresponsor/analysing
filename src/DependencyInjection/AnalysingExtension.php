<?php

declare(strict_types=1);

namespace App\Analysing\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

/**
 * Loads the Symfony-native service export for the Analysing package without a YAML dependency.
 */
final class AnalysingExtension extends Extension
{
    /**
     * @param array<int, array<string, mixed>> $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        unset($configs);

        $projectRoot = \dirname(__DIR__, 2);

        $candidates = [
            $projectRoot.'/config/component/services.php',
            $projectRoot.'/config/services.php',
        ];

        $loader = new PhpFileLoader($container, new FileLocator($projectRoot));

        foreach ($candidates as $candidate) {
            if (!\is_file($candidate)) {
                continue;
            }

            $loader->load($candidate);

            return;
        }
    }
}
