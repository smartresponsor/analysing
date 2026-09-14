<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\EventSubscriber\Http\AnalyticsVendorContextResponseSubscriber;
use App\Analysing\EventSubscriber\Http\AnalyticsVendorContextSubscriber;
use App\Analysing\Resolver\Http\AnalyticsVendorContextResolver;
use App\Analysing\Service\Http\AnalyticsVendorContext;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class VendorContextSubscriberTest extends TestCase
{
    public function testVendorIsStoredOnRequestAndResponse(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/analytics/health', 'GET');
        $request->headers->set('X-SR-VENDOR', 'acme');

        $context = new AnalyticsVendorContext();
        $requestSubscriber = new AnalyticsVendorContextSubscriber(new AnalyticsVendorContextResolver(), $context);
        $requestEvent = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
        $requestSubscriber->onKernelRequest($requestEvent);

        self::assertSame('acme', $request->attributes->get('_analytics_vendor'));
        self::assertSame('acme', $context->current());

        $responseSubscriber = new AnalyticsVendorContextResponseSubscriber($context);
        $response = new Response();
        $responseEvent = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);
        $responseSubscriber->onKernelResponse($responseEvent);

        self::assertSame('acme', $response->headers->get('X-SR-VENDOR'));
    }
}
