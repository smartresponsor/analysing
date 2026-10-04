<?php

declare(strict_types=1);

namespace App\Analysing\FactoryInterface\Doctrine;

interface AnalyticsEntityManagerFactoryInterface
{
    public static function create(): object;
}
