<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\EventSubscriber\Http\AnalyticsRequestAuthSubscriber;
use App\Analysing\FactoryInterface\Http\AnalyticsErrorResponseFactoryInterface;
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
}
