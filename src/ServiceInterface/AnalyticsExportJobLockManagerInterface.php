<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface;

interface AnalyticsExportJobLockManagerInterface
{
    /** @return resource|null */
    public function acquire(int $jobId);

    /** @param resource $handle */
    public function release($handle): void;

    public function isLocked(int $jobId): bool;
}
