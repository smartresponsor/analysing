<?php

declare(strict_types=1);

namespace App\Analysing\Service\Http;

use App\Analysing\ServiceInterface\Http\TenantContextInterface;
use App\Analysing\ServiceInterface\Http\TenantContextResolverInterface;
use App\Analysing\ServiceInterface\Http\TenantContextSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class TenantContextSubscriber implements TenantContextSubscriberInterface
{
    public function __construct(
        private TenantContextResolverInterface $resolver,
        private TenantContextInterface $context,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', -8],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $tenant = $this->resolver->resolve($request);
        $request->attributes->set('_analytics_tenant', $tenant);
        $this->context->set($tenant);
    }
}
