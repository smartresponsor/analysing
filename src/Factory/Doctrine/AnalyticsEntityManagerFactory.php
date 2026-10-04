<?php

declare(strict_types=1);

namespace App\Analysing\Factory\Doctrine;

use App\Analysing\FactoryInterface\Doctrine\AnalyticsEntityManagerFactoryInterface;
use App\Analysing\Repository\AnalyticsEntityManagerRepository;

final class AnalyticsEntityManagerFactory implements AnalyticsEntityManagerFactoryInterface
{
    public static function create(): object
    {
        return AnalyticsEntityManagerRepository::create();
    }
}
