<?php

declare(strict_types=1);

namespace App\ServiceInterface\Analytics;

interface AccessGuardInterface
{
    public function allow(string $subject): bool;
}
