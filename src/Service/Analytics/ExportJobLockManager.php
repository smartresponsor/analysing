<?php

declare(strict_types=1);

namespace App\Service\Analytics;

final class ExportJobLockManager
{
    /**
     * @return resource|null
     */
    public function acquire(int $jobId)
    {
        if ($jobId <= 0) {
            throw new \InvalidArgumentException('Export job id must be positive for locking.');
        }

        $path = $this->getPath($jobId);
        $handle = fopen($path, 'c+');
        if (false === $handle) {
            throw new \RuntimeException('Unable to open export job lock file.');
        }

        if (!flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);

            return null;
        }

        ftruncate($handle, 0);
        fwrite($handle, (string) getmypid());

        return $handle;
    }

    /**
     * @param resource $handle
     */
    public function release($handle): void
    {
        flock($handle, LOCK_UN);
        fclose($handle);
    }

    public function isLocked(int $jobId): bool
    {
        $handle = $this->acquire($jobId);
        if (null === $handle) {
            return true;
        }

        $this->release($handle);

        return false;
    }

    private function getPath(int $jobId): string
    {
        return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'analytics_export_job_'.$jobId.'.lock';
    }
}
