<?php

declare(strict_types=1);

namespace App\Analysing\FactoryInterface\Doctrine;

use Doctrine\ORM\EntityManagerInterface;

interface AnalyticsEntityManagerFactoryInterface
{
    public static function create(): EntityManagerInterface;
}
