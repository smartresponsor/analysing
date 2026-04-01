<?php

declare(strict_types=1);

namespace App\Service\Analytics;

/**
 * Manages file-based locks for export jobs.
 *
 * The current implementation uses lock files in the system temporary directory, which makes it a
 * lightweight locking mechanism suitable for single-host or shared-filesystem worker setups.
 */
final class ExportJobLockManager
{
    /**
     * Acquires an exclusive non-blocking lock for the given export job.
     *
     * @param int $jobId Positive export job identifier.
     *
     * @return resource|null An open lock handle when the lock is acquired, or null when the job is already locked.
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
     * Releases a previously acquired export job lock.
     *
     * @param resource $handle Open lock handle returned by {@see acquire()}.
     */
    public function release($handle): void
    {
        flock($handle, LOCK_UN);
        fclose($handle);
    }

    /**
     * Checks whether the given export job is currently locked.
     *
     * @param int $jobId Export job identifier.
     *
     * @return bool True when the job is locked by another process, false otherwise.
     */
    public function isLocked(int $jobId): bool
    {
        $handle = $this->acquire($jobId);
        if (null === $handle) {
            return true;
        }

        $this->release($handle);

        return false;
    }

    /**
     * Builds the absolute path to the lock file for a job.
     *
     * @param int $jobId Export job identifier.
     *
     * @return string Absolute path to the lock file.
     */
    private function getPath(int $jobId): string
    {
        return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'analytics_export_job_'.$jobId.'.lock';
    }
}
