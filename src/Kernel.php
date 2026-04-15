<?php

declare(strict_types=1);

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        $contents = require $this->getProjectDir().'/config/bundles.php';
        foreach ($contents as $class => $envs) {
            if (!is_string($class) || !is_a($class, BundleInterface::class, true)) {
                continue;
            }

            if (($envs['all'] ?? false) || ($envs[$this->environment] ?? false)) {
                $bundle = new $class();
                yield $bundle;
            }
        }
    }

    /**
     * @throws \Throwable
     */
    protected function configureContainer(ContainerConfigurator $container, LoaderInterface $loader): void
    {
        $container->parameters();
        $configDir = $this->getProjectDir().'/config';

        if (is_file($configDir.'/packages/framework.php')) {
            $loader->load($configDir.'/packages/framework.php');
        }

        if (class_exists('Symfony\\Component\\Yaml\\Yaml')) {
            $loader->load($configDir.'/packages/*.yaml', 'glob');
        }

        $loader->load($configDir.'/services.php');

        if (class_exists('Symfony\\Component\\Yaml\\Yaml') && is_file($configDir.'/services.yaml')) {
            $loader->load($configDir.'/services.yaml');
        }
    }

    /**
     * @throws \Throwable
     */
    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $configDir = $this->getProjectDir().'/config';

        $routes->import($configDir.'/routes.php');

        if (class_exists('Symfony\\Component\\Yaml\\Yaml') && is_file($configDir.'/routes.yaml')) {
            $routes->import($configDir.'/routes.yaml');
        }
    }
}
