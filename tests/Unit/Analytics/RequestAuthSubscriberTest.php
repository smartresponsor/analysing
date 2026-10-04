<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\EventSubscriber\Http\AnalyticsRequestAuthSubscriber;
use App\Analysing\FactoryInterface\Http\AnalyticsErrorResponseFactoryInterface;
use App\Analysing\Resolver\Http\AnalyticsVendorContextResolver;
use App\Analysing\Resolver\Http\AnalyticsVendorContextResolverInterface;
use App\Analysing\ServiceInterface\AnalyticsTokenServiceInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class RequestAuthSubscriberTest extends TestCase
{
    public function testMalformedListScopeIsRejectedInsteadOfThrowing(): void
    {
        $tokens = $this->createMock(AnalyticsTokenServiceInterface::class);
        $tokens->expects(self::once())
            ->method('verify')
            ->with('token-value')
            ->willReturn(['scope' => ['analytics_export']]);

        $errors = $this->createMock(AnalyticsErrorResponseFactoryInterface::class);
        $errors->expects(self::once())
            ->method('create')
            ->with(
                'analytics_export',
                'Analytics authorization scope is invalid.',
                'analytics.auth.invalid_scope',
                Response::HTTP_FORBIDDEN,
                self::isType('float'),
            )
            ->willReturn(new JsonResponse(['error_code' => 'analytics.auth.invalid_scope'], Response::HTTP_FORBIDDEN));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('warning');

        $vendorResolver = $this->createMock(AnalyticsVendorContextResolverInterface::class);
        $vendorResolver->expects(self::never())->method('resolve');

        $subscriber = new AnalyticsRequestAuthSubscriber(
            $tokens,
            $logger,
            $errors,
            $vendorResolver,
            required: true,
            publicRead: false,
        );

        $request = Request::create('/analytics/export', 'POST');
        $request->attributes->set('_route', 'analytics_export');
        $request->headers->set('X-Analytics-Token', 'token-value');
        $event = new RequestEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);

        $subscriber->onKernelRequest($event);

        self::assertTrue($event->hasResponse());
        self::assertSame(Response::HTTP_FORBIDDEN, $event->getResponse()->getStatusCode());
    }

    public function testScopedVendorMismatchIsRejected(): void
    {
        $tokens = $this->createMock(AnalyticsTokenServiceInterface::class);
        $tokens->expects(self::once())
            ->method('verify')
            ->with('token-value')
            ->willReturn(['scope' => ['routes' => ['analytics_export'], 'vendor' => 'vendor-a']]);

        $errors = $this->createMock(AnalyticsErrorResponseFactoryInterface::class);
        $errors->expects(self::once())
            ->method('create')
            ->with(
                'analytics_export',
                'Analytics authorization vendor does not match the request.',
                'analytics.auth.vendor_mismatch',
                Response::HTTP_FORBIDDEN,
                self::isType('float'),
            )
            ->willReturn(new JsonResponse(['error_code' => 'analytics.auth.vendor_mismatch'], Response::HTTP_FORBIDDEN));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('warning');

        $subscriber = new AnalyticsRequestAuthSubscriber(
            $tokens,
            $logger,
            $errors,
            new AnalyticsVendorContextResolver(),
            required: true,
            publicRead: false,
        );

        $request = Request::create('/analytics/export', 'POST');
        $request->attributes->set('_route', 'analytics_export');
        $request->headers->set('X-Analytics-Token', 'token-value');
        $request->headers->set('X-SR-VENDOR', 'vendor-b');
        $event = new RequestEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);

        $subscriber->onKernelRequest($event);

        self::assertTrue($event->hasResponse());
        self::assertSame(Response::HTTP_FORBIDDEN, $event->getResponse()->getStatusCode());
        self::assertNull($request->attributes->get('_analytics_token_vendor'));
    }

    public function testScopedVendorBecomesRequestContextWhenNoExplicitVendorExists(): void
    {
        $claims = ['scope' => ['routes' => ['analytics_export'], 'vendor' => 'vendor-a'], 'subject' => 'ignored'];
        $tokens = $this->createMock(AnalyticsTokenServiceInterface::class);
        $tokens->expects(self::once())
            ->method('verify')
            ->with('token-value')
            ->willReturn($claims);

        $errors = $this->createMock(AnalyticsErrorResponseFactoryInterface::class);
        $errors->expects(self::never())->method('create');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('warning');

        $subscriber = new AnalyticsRequestAuthSubscriber(
            $tokens,
            $logger,
            $errors,
            new AnalyticsVendorContextResolver(),
            required: true,
            publicRead: false,
        );

        $request = Request::create('/analytics/export', 'POST');
        $request->attributes->set('_route', 'analytics_export');
        $request->headers->set('X-Analytics-Token', 'token-value');
        $event = new RequestEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);

        $subscriber->onKernelRequest($event);

        self::assertFalse($event->hasResponse());
        self::assertSame('vendor-a', $request->attributes->get('_analytics_token_vendor'));
        self::assertSame($claims, $request->attributes->get('_analytics_token_claims'));
        self::assertSame('vendor-a', (new AnalyticsVendorContextResolver())->resolve($request));
    }
}
