<?php

declare(strict_types=1);

namespace App\Service\Http;

use App\ServiceInterface\Http\RequestCorrelationIdProviderInterface;
use App\ServiceInterface\Http\RequestCorrelationIdSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class RequestCorrelationIdSubscriber implements RequestCorrelationIdSubscriberInterface
{
    public function __construct(
        private RequestCorrelationIdProviderInterface $provider,
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
