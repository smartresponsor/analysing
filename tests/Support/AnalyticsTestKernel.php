<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Support;

use App\Analysing\AnalysingBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

final class AnalyticsTestKernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new AnalysingBundle();
    }

    /**
     * @throws \Throwable
     */
    protected function configureContainer(ContainerConfigurator $container, LoaderInterface $loader): void
    {
        $configDir = $this->getProjectDir().'/config';

        if (is_file($configDir.'/packages/framework.php')) {
            $loader->load($configDir.'/packages/framework.php');
        }

        if (is_file($configDir.'/services.php')) {
            $loader->load($configDir.'/services.php');
        }
    }

    /**
     * @throws \Throwable
     */
    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $configDir = $this->getProjectDir().'/config';

        if (is_file($configDir.'/routes.php')) {
            $routes->import($configDir.'/routes.php');
        }
    }
}
