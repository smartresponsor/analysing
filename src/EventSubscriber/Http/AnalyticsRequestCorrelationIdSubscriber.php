<?php

declare(strict_types=1);

namespace App\Analysing\EventSubscriber\Http;

use App\Analysing\ProviderInterface\Http\AnalyticsRequestCorrelationIdProviderInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class AnalyticsRequestCorrelationIdSubscriber implements AnalyticsRequestCorrelationIdSubscriberInterface
{
    public function __construct(
        private AnalyticsRequestCorrelationIdProviderInterface $provider,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 256],
            KernelEvents::RESPONSE => ['onKernelResponse', -256],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $this->provider->initialize($event->getRequest());
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $event->getResponse()->headers->set('X-Correlation-ID', $this->provider->current());
    }
}
