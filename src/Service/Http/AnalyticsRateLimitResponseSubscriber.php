<?php

declare(strict_types=1);

namespace App\Analysing\Service\Http;

use App\Analysing\ServiceInterface\Http\AnalyticsRateLimitResponseSubscriberInterface;
use App\Analysing\ValueObject\Http\AnalyticsRateLimitDecision;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class AnalyticsRateLimitResponseSubscriber implements AnalyticsRateLimitResponseSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $decision = $event->getRequest()->attributes->get('_analytics_rate_limit_decision');
        if (!$decision instanceof AnalyticsRateLimitDecision || !$decision->allowed) {
            return;
        }

        $response = $event->getResponse();
        $response->headers->set('X-RateLimit-Limit', (string) $decision->limit);
        $response->headers->set('X-RateLimit-Remaining', (string) $decision->remaining);
        $response->headers->set('X-RateLimit-Reset', (string) $decision->resetAt);
    }
}
