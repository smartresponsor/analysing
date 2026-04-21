<?php

declare(strict_types=1);

namespace App\Analysing;

use App\Analysing\DependencyInjection\AnalysingExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Symfony bundle facade for the Analysing RC component.
 *
 * The component remains responsible for its own business surface.
 * The host application only enables this bundle and imports routes when needed.
 */
final class AnalysingBundle extends Bundle
{
    public function getContainerExtension(): ExtensionInterface
    {
        return new AnalysingExtension();
    }
}
