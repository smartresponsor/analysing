<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Service\Http\AnalyticsErrorResponseFactory;
use App\Service\Http\AnalyticsSuccessResponseFactory;
use App\Service\Http\RequestCorrelationIdProvider;
use App\Service\Http\TenantContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

trait AnalyticsHttpFactoriesTrait
{
    private function createSuccessFactory(?Request $request = null): AnalyticsSuccessResponseFactory
    {
        return new AnalyticsSuccessResponseFactory($this->createCorrelationProvider($request), new TenantContext());
    }

    private function createErrorFactory(?Request $request = null): AnalyticsErrorResponseFactory
    {
        return new AnalyticsErrorResponseFactory($this->createCorrelationProvider($request), new TenantContext());
    }

    private function createCorrelationProvider(?Request $request = null): RequestCorrelationIdProvider
    {
        $requestStack = new RequestStack();
        if ($request instanceof Request) {
            $requestStack->push($request);
        }

        return new RequestCorrelationIdProvider($requestStack);
    }
}
