<?php

declare(strict_types=1);

namespace App\Analysing\EventSubscriber\Http;

use App\Analysing\ServiceInterface\Http\AnalyticsVendorContextInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class AnalyticsVendorContextResponseSubscriber implements AnalyticsVendorContextResponseSubscriberInterface
{
    public function __construct(
        private AnalyticsVendorContextInterface $context,
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

        $event->getResponse()->headers->set('X-SR-VENDOR', $this->context->current());
    }
}
