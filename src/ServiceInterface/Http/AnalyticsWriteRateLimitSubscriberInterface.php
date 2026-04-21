<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface\Http;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

interface AnalyticsWriteRateLimitSubscriberInterface extends EventSubscriberInterface
{
    public function onKernelRequest(RequestEvent $event): void;
}
