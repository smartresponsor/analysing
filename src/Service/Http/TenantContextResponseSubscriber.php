<?php

declare(strict_types=1);

namespace App\Analysing\Service\Http;

use App\Analysing\ServiceInterface\Http\TenantContextInterface;
use App\Analysing\ServiceInterface\Http\TenantContextResponseSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class TenantContextResponseSubscriber implements TenantContextResponseSubscriberInterface
{
    public function __construct(
        private TenantContextInterface $context,
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
