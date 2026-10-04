<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Unit\Analytics;

use App\Analysing\EventSubscriber\Http\AnalyticsIdempotencyRequestSubscriber;
use App\Analysing\FactoryInterface\Http\AnalyticsErrorResponseFactoryInterface;
use App\Analysing\Resolver\Http\AnalyticsVendorContextResolverInterface;
use App\Analysing\ServiceInterface\Http\AnalyticsIdempotencyStoreInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class IdempotencyRequestSubscriberTest extends TestCase
{
    public function testConflictDecisionReturnsConflictAndAttachesRequestContext(): void
    {
        $store = $this->createMock(AnalyticsIdempotencyStoreInterface::class);
        $store->expects(self::once())->method('isValidKey')->with('idem-key')->willReturn(true);
        $store->expects(self::once())
            ->method('begin')
            ->with('analytics_export', 'vendor-7', 'idem-key', self::isType('string'))
            ->willReturn(['status' => 'conflict']);

        $errors = $this->createMock(AnalyticsErrorResponseFactoryInterface::class);
        $errors->expects(self::once())
            ->method('create')
            ->with(
                'analytics_export',
                'Analytics idempotency key was already used with a different request.',
                'analytics.idempotency.conflict',
                Response::HTTP_CONFLICT,
                self::isType('float'),
                false,
                ['vendor' => 'vendor-7'],
            )
            ->willReturn(new JsonResponse(['error_code' => 'analytics.idempotency.conflict'], Response::HTTP_CONFLICT));

        $vendorResolver = $this->createMock(AnalyticsVendorContextResolverInterface::class);
        $vendorResolver->expects(self::once())->method('resolve')->willReturn('vendor-7');

        $event = $this->event('POST');
        (new AnalyticsIdempotencyRequestSubscriber($store, $errors, $vendorResolver))->onKernelRequest($event);

        self::assertTrue($event->hasResponse());
        self::assertSame(Response::HTTP_CONFLICT, $event->getResponse()->getStatusCode());
        self::assertSame('idem-key', $event->getResponse()->headers->get('X-Idempotency-Key'));
        self::assertSame('vendor-7', $event->getRequest()->attributes->get('_analytics_idempotency_vendor'));
        $fingerprint = $event->getRequest()->attributes->get('_analytics_idempotency_fingerprint');
        self::assertIsString($fingerprint);
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $fingerprint);
    }

    public function testReplayDecisionUsesStoredResponseAndReplayHeaders(): void
    {
        $record = ['status' => 202, 'body' => ['queued' => true]];

        $store = $this->createMock(AnalyticsIdempotencyStoreInterface::class);
        $store->expects(self::once())->method('isValidKey')->with('idem-key')->willReturn(true);
        $store->expects(self::once())
            ->method('begin')
            ->willReturn(['status' => 'replay', 'record' => $record]);
        $store->expects(self::once())
            ->method('buildReplayResponse')
            ->with($record)
            ->willReturn(new JsonResponse(['queued' => true], Response::HTTP_ACCEPTED));

        $errors = $this->createMock(AnalyticsErrorResponseFactoryInterface::class);
        $errors->expects(self::never())->method('create');

        $vendorResolver = $this->createMock(AnalyticsVendorContextResolverInterface::class);
        $vendorResolver->expects(self::once())->method('resolve')->willReturn('vendor-7');

        $event = $this->event('PATCH');
        (new AnalyticsIdempotencyRequestSubscriber($store, $errors, $vendorResolver))->onKernelRequest($event);

        self::assertTrue($event->hasResponse());
        self::assertSame(Response::HTTP_ACCEPTED, $event->getResponse()->getStatusCode());
        self::assertSame('idem-key', $event->getResponse()->headers->get('X-Idempotency-Key'));
        self::assertSame('replayed', $event->getResponse()->headers->get('Idempotency-Status'));
    }

    private function event(string $method): RequestEvent
    {
        $request = Request::create(
            '/analytics/export?format=json',
            $method,
            server: ['CONTENT_TYPE' => 'application/json'],
            content: '{"metric":"revenue"}',
        );
        $request->attributes->set('_route', 'analytics_export');
        $request->headers->set('X-Idempotency-Key', 'idem-key');

        return new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
        );
    }
}
