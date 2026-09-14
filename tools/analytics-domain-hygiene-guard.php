<?php
declare(strict_types=1);

/*
 * Marketing America Corp. Oleksandr Tishchenko
 * Comments in English only. Singular naming.
 */

final class AnalyticsDomainHygieneGuard
{
    private string $root;

    public function __construct(string $root)
    {
        $this->root = rtrim($root, '/');
    }

    public function run(): int
    {
        $domainRoot = $this->root . '/src/Domain';
        $domainInterfaceRoot = $this->root . '/src/DomainInterface';
        if (!is_dir($domainRoot)) {
            echo "OK: forbidden src/Domain root is absent.\n";
            return 0;
        }

        $domainDir = $this->root . '/src/Domain/Analytics';
        $domainInterfaceDir = $this->root . '/src/DomainInterface/Analytics';
        if (!is_dir($domainDir)) {
            fwrite(STDERR, "Domain directory not found: {$domainDir}\n");
            return 2;
        }

        if (is_dir($domainInterfaceDir) === false) {
            fwrite(STDERR, "DomainInterface directory not found: {$domainInterfaceDir}\n");
            return 2;
        }

        $bannedRegex = [
            '/\.distInterface\.php$/',
            '/(^|\/)(ingest|materialize|metrics|aggregate_funnel|index)Interface\.php$/',
            '/Command\\.php$/',
            '/SmokeTest\\.php$/',
            '/Controller\\.php$/',
            '/ControllerInterface\\.php$/',
            '/Repository\\.php$/',
            '/RepositoryInterface\\.php$/',
            '/Service\\.php$/',
            '/ServiceInterface\\.php$/',
            '/php-cs-fixer\\.dist\\.php$/',
            '/(^|\\/)(ingest|materialize|metrics|aggregate_funnel|index)\\.php$/',
        ];

        $bad = [];
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($domainDir, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($it as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $path = $file->getPathname();
            foreach ($bannedRegex as $re) {
                if (preg_match($re, $path) === 1) {
                    $rel = ltrim(substr($path, strlen($this->root)), '/');
                    $bad[] = $rel;
                    break;
                }
            }
        }


        $it2 = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($domainInterfaceDir, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($it2 as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $path = $file->getPathname();
            foreach ($bannedRegex as $re) {
                if (preg_match($re, $path) === 1) {
                    $rel = ltrim(substr($path, strlen($this->root)), '/');
                    $bad[] = $rel;
                    break;
                }
            }
        }
        sort($bad);
        if (!$bad) {
            echo "OK: Domain layer is clean.\n";
            return 0;
        }

        fwrite(STDERR, "FAIL: Domain layer contains non-domain artifacts:\n");
        foreach ($bad as $p) {
            fwrite(STDERR, " - {$p}\n");
        }

        return 2;
    }
}

$guard = new AnalyticsDomainHygieneGuard(getcwd());
exit($guard->run());
