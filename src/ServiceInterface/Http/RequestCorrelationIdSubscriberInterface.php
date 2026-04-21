<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface\Http;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

interface RequestCorrelationIdSubscriberInterface extends EventSubscriberInterface
{
    public function onKernelRequest(RequestEvent $event): void;

    public function onKernelResponse(ResponseEvent $event): void;
}
