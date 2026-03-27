<?php

/*
 * Marketing America Corp. Oleksandr Tishchenko
 */

declare(strict_types=1);

final class AnalyticsPhpHeaderGuard
{
    /** @var string[] */
    private array $excludeDirName = ['vendor', '.git', '.idea', '.phpunit.cache', 'var', 'cache', 'report'];

    public static function main(array $argv): int
    {
        $root = getcwd() ?: '.';
        $dirs = [];
        foreach (['src', 'tools', 'tests', 'SourceDomain'] as $dir) {
            if (is_dir($root . DIRECTORY_SEPARATOR . $dir)) {
                $dirs[] = $root . DIRECTORY_SEPARATOR . $dir;
            }
        }

        if ($dirs === []) {
            fwrite(STDERR, "No scan dirs found\n");
            return 2;
        }

        $guard = new self();
        $bad = [];
        foreach ($dirs as $dir) {
            foreach ($guard->findPhpFiles($dir) as $file) {
                if (!$guard->isHeaderValid($file)) {
                    $bad[] = self::relPath($file, $root);
                }
            }
        }

        if ($bad !== []) {
            sort($bad);
            fwrite(STDERR, "Invalid PHP header (content before <?php):\n");
            foreach ($bad as $p) {
                fwrite(STDERR, "- {$p}\n");
            }
            return 1;
        }

        fwrite(STDOUT, "OK\n");
        return 0;
    }

    /** @return iterable<string> */
    private function findPhpFiles(string $dir): iterable
    {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($it as $fileInfo) {
            if (!$fileInfo instanceof SplFileInfo) {
                continue;
            }

            if ($fileInfo->isDir()) {
                continue;
            }

            $path = $fileInfo->getPathname();
            if (pathinfo($path, PATHINFO_EXTENSION) !== 'php') {
                continue;
            }

            foreach ($this->excludeDirName as $exclude) {
                if (str_contains($path, DIRECTORY_SEPARATOR . $exclude . DIRECTORY_SEPARATOR)) {
                    continue 2;
                }
            }

            yield $path;
        }
    }

    private function isHeaderValid(string $file): bool
    {
        $data = file_get_contents($file, false, null, 0, 1024);
        if (!is_string($data) || $data === '') {
            return true;
        }

        if (str_starts_with($data, "\xEF\xBB\xBF")) {
            $data = substr($data, 3);
        }

        $trim = ltrim($data);
        return str_starts_with($trim, '<?php');
    }

    private static function relPath(string $absPath, string $root): string
    {
        $root = rtrim(str_replace('\\', '/', $root), '/');
        $p = str_replace('\\', '/', $absPath);

        if (str_starts_with($p, $root . '/')) {
            return substr($p, strlen($root) + 1);
        }

        return $p;
    }
}

exit(AnalyticsPhpHeaderGuard::main($argv));
