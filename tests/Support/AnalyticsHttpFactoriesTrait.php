<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Support;

use App\Analysing\Service\Http\AnalyticsErrorResponseFactory;
use App\Analysing\Service\Http\AnalyticsSuccessResponseFactory;
use App\Analysing\Service\Http\RequestCorrelationIdProvider;
use App\Analysing\Service\Http\TenantContext;
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
