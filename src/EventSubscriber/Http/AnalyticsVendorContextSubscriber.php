<?php

declare(strict_types=1);

namespace App\Analysing\EventSubscriber\Http;

use App\Analysing\Resolver\Http\AnalyticsVendorContextResolverInterface;
use App\Analysing\ServiceInterface\Http\AnalyticsVendorContextInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class AnalyticsVendorContextSubscriber implements AnalyticsVendorContextSubscriberInterface
{
    public function __construct(
        private AnalyticsVendorContextResolverInterface $resolver,
        private AnalyticsVendorContextInterface $context,
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
        $vendor = $this->resolver->resolve($request);
        $request->attributes->set('_analytics_vendor', $vendor);
        $this->context->set($vendor);
    }
}
