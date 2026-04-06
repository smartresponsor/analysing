<?php

declare(strict_types=1);

namespace App\Tests\Unit\Analytics;

use App\Service\Http\TenantContext;
use App\Service\Http\TenantContextResolver;
use App\Service\Http\TenantContextResponseSubscriber;
use App\Service\Http\TenantContextSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class TenantContextSubscriberTest extends TestCase
{
    public function testTenantIsStoredOnRequestAndResponse(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/analytics/health', 'GET');
        $request->headers->set('X-SR-TENANT', 'acme');

        $context = new TenantContext();
        $requestSubscriber = new TenantContextSubscriber(new TenantContextResolver(), $context);
        $requestEvent = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
        $requestSubscriber->onKernelRequest($requestEvent);

        self::assertSame('acme', $request->attributes->get('_analytics_tenant'));
        self::assertSame('acme', $context->current());

        $responseSubscriber = new TenantContextResponseSubscriber($context);
        $response = new Response();
        $responseEvent = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);
        $responseSubscriber->onKernelResponse($responseEvent);

        self::assertSame('acme', $response->headers->get('X-SR-TENANT'));
    }
}
