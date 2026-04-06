<?php

declare(strict_types=1);

namespace App\Service\Http;

use App\ServiceInterface\Http\TenantContextInterface;
use App\ServiceInterface\Http\TenantContextResponseSubscriberInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class TenantContextResponseSubscriber implements TenantContextResponseSubscriberInterface
{
    public function __construct(
        private readonly TenantContextInterface $context,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', -240],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $event->getResponse()->headers->set('X-SR-TENANT', $this->context->current());
    }
}
