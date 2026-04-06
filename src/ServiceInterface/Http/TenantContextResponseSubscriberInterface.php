<?php

declare(strict_types=1);

namespace App\ServiceInterface\Http;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

interface TenantContextResponseSubscriberInterface extends EventSubscriberInterface
{
    public function onKernelResponse(ResponseEvent $event): void;
}
