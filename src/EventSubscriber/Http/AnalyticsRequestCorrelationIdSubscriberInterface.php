<?php

declare(strict_types=1);

namespace App\Analysing\EventSubscriber\Http;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

interface AnalyticsRequestCorrelationIdSubscriberInterface extends EventSubscriberInterface
{
    public function onKernelRequest(RequestEvent $event): void;

    public function onKernelResponse(ResponseEvent $event): void;
}
