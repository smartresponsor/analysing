<?php

declare(strict_types=1);

namespace App\Analysing\Tests\Support;

use App\Analysing\Factory\Http\AnalyticsErrorResponseFactory;
use App\Analysing\Factory\Http\AnalyticsSuccessResponseFactory;
use App\Analysing\Provider\Http\AnalyticsRequestCorrelationIdProvider;
use App\Analysing\Service\Http\AnalyticsVendorContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

trait AnalyticsHttpFactoriesTrait
{
    private function createSuccessFactory(?Request $request = null): AnalyticsSuccessResponseFactory
    {
        return new AnalyticsSuccessResponseFactory($this->createCorrelationProvider($request), new AnalyticsVendorContext());
    }

    private function createErrorFactory(?Request $request = null): AnalyticsErrorResponseFactory
    {
        return new AnalyticsErrorResponseFactory($this->createCorrelationProvider($request), new AnalyticsVendorContext());
    }

    private function createCorrelationProvider(?Request $request = null): AnalyticsRequestCorrelationIdProvider
    {
        $requestStack = new RequestStack();
        if ($request instanceof Request) {
            $requestStack->push($request);
        }

        return new AnalyticsRequestCorrelationIdProvider($requestStack);
    }
}
