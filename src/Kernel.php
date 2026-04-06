<?php

declare(strict_types=1);

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Yaml\Yaml;

final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    /** @return iterable<BundleInterface> */
    public function registerBundles(): iterable
    {
        $contents = require $this->getProjectDir().'/config/bundles.php';

        foreach ($contents as $class => $envs) {
            if (!is_string($class) || !is_subclass_of($class, BundleInterface::class)) {
                continue;
            }

            if (($envs[$this->environment] ?? $envs['all'] ?? false) === true) {
                yield new $class();
            }
        }
    }

    public function getProjectDir(): string
    {
        return \dirname(__DIR__);
    }

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $configDir = $this->getProjectDir().'/config';

        $loader->load($configDir.'/packages/*.php', 'glob');
        $loader->load($configDir.'/services.php');

        if (class_exists(Yaml::class)) {
            $loader->load($configDir.'/packages/*.yaml', 'glob');
            $loader->load($configDir.'/services.yaml');
        }
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $configDir = $this->getProjectDir().'/config';

        $routes->import($configDir.'/routes.php');

        if (class_exists(Yaml::class)) {
            $routes->import($configDir.'/routes.yaml');
        }
    }
}
