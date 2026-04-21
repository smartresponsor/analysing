<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface\Analytics;

interface AccessGuardInterface
{
    public function allow(string $subject): bool;
}
