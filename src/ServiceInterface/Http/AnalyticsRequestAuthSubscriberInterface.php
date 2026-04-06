<?php

declare(strict_types=1);

namespace App\ServiceInterface\Http;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

interface AnalyticsRequestAuthSubscriberInterface extends EventSubscriberInterface
{
    public function onKernelRequest(RequestEvent $event): void;
}
