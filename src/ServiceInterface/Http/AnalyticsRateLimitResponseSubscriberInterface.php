<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface\Http;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

interface AnalyticsRateLimitResponseSubscriberInterface extends EventSubscriberInterface
{
    public function onKernelResponse(ResponseEvent $event): void;
}
