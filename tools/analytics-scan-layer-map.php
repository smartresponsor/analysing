<?php

/*
 * Marketing America Corp. Oleksandr Tishchenko
 */

declare(strict_types=1);

final class AnalyticsScanLayerMap
{
    /** @var string[] */
    private array $rootDirs;

    /** @var string[] */
    private array $excludeDirName;

    public function __construct(array $rootDirs)
    {
        $this->rootDirs = $rootDirs;
        $this->excludeDirName = ['vendor', '.git', '.idea', '.phpunit.cache', 'var', 'cache'];
    }

    public static function main(array $argv): int
    {
        $opt = self::parseArgv($argv);
        $cwd = getcwd() ?: '.';

        $root = $opt['root'] ?? $cwd;
        $outDir = $opt['out'] ?? ($root . DIRECTORY_SEPARATOR . 'report');

        $scanDirs = [];
        foreach (['src', 'SourceDomain', 'sourceDomain'] as $dir) {
            $p = $root . DIRECTORY_SEPARATOR . $dir;
            if (is_dir($p)) {
                $scanDirs[] = $p;
            }
        }

        if ($scanDirs === []) {
            fwrite(STDERR, "No scan dirs found under root: {$root}\n");
            return 2;
        }

        if (!is_dir($outDir) && !mkdir($outDir, 0775, true) && !is_dir($outDir)) {
            fwrite(STDERR, "Cannot create output dir: {$outDir}\n");
            return 3;
        }

        $scanner = new self($scanDirs);
        $result = $scanner->scan($root);

        $jsonPath = rtrim($outDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'analytics-layer-map.json';
        $csvPath = rtrim($outDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'analytics-duplicate-basename.csv';

        file_put_contents($jsonPath, json_encode($result['map'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
        file_put_contents($csvPath, self::renderDuplicateCsv($result['duplicate']));

        $summary = [
            'files_total' => count($result['map']),
            'duplicate_basename_total' => count($result['duplicate']),
            'source_domain_candidate_total' => $result['sourceDomainCount'],
            'interface_interface_total' => $result['interfaceInterfaceCount'],
            'mirror_missing_total' => $result['mirrorMissingCount'],
        ];

        $summaryPath = rtrim($outDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'analytics-scan-summary.json';
        file_put_contents($summaryPath, json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

        fwrite(STDOUT, "OK\n");
        fwrite(STDOUT, "- files: {$summary['files_total']}\n");
        fwrite(STDOUT, "- duplicate basenames: {$summary['duplicate_basename_total']}\n");
        fwrite(STDOUT, "- sourceDomain candidates: {$summary['source_domain_candidate_total']}\n");
        fwrite(STDOUT, "- InterfaceInterface anomalies: {$summary['interface_interface_total']}\n");
        fwrite(STDOUT, "- mirror missing: {$summary['mirror_missing_total']}\n");

        return 0;
    }

    /**
     * @return array{map: array<int, array<string, mixed>>, duplicate: array<int, array{basename: string, count: int, paths: string}>, sourceDomainCount: int, interfaceInterfaceCount: int, mirrorMissingCount: int}
     */
    public function scan(string $root): array
    {
        $map = [];
        $basenameToPath = [];

        $sourceDomainCount = 0;
        $interfaceInterfaceCount = 0;
        $mirrorMissingCount = 0;

        foreach ($this->rootDirs as $dir) {
            foreach ($this->findPhpFiles($dir) as $file) {
                $rel = self::relPath($file, $root);

                $info = self::readPhpSymbolInfo($file);

                $layerInfo = self::detectLayerFromPath($rel);
                $isSourceDomain = $layerInfo['isSourceDomain'];

                if ($isSourceDomain) {
                    $sourceDomainCount++;
                }

                $issues = [];

                if ($info['symbol'] !== null && str_contains($info['symbol'], 'InterfaceInterface')) {
                    $issues[] = 'interfaceinterface';
                    $interfaceInterfaceCount++;
                }

                $mirror = self::mirrorCheck($rel);
                if ($mirror['missing'] === true) {
                    $issues[] = 'mirror_missing';
                    $mirrorMissingCount++;
                }

                $targetGuess = self::guessTargetPath($rel, $info);

                $map[] = [
                    'path' => $rel,
                    'layer' => $layerInfo['layer'],
                    'subdomain' => $layerInfo['subdomain'],
                    'is_source_domain' => $isSourceDomain,
                    'namespace' => $info['namespace'],
                    'symbol_type' => $info['symbolType'],
                    'symbol' => $info['symbol'],
                    'fqcn' => $info['fqcn'],
                    'mirror' => $mirror,
                    'target_guess' => $targetGuess,
                    'issues' => $issues,
                ];

                $basename = pathinfo($file, PATHINFO_FILENAME);
                $basenameToPath[$basename] ??= [];
                $basenameToPath[$basename][] = $rel;
            }
        }

        $duplicate = [];
        foreach ($basenameToPath as $basename => $paths) {
            if (count($paths) <= 1) {
                continue;
            }

            sort($paths);
            $duplicate[] = [
                'basename' => (string) $basename,
                'count' => count($paths),
                'paths' => implode(' | ', $paths),
            ];
        }

        usort($duplicate, static function (array $a, array $b): int {
            return $b['count'] <=> $a['count'];
        });

        return [
            'map' => $map,
            'duplicate' => $duplicate,
            'sourceDomainCount' => $sourceDomainCount,
            'interfaceInterfaceCount' => $interfaceInterfaceCount,
            'mirrorMissingCount' => $mirrorMissingCount,
        ];
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

    /** @return array{namespace: string|null, symbolType: string|null, symbol: string|null, fqcn: string|null} */
    private static function readPhpSymbolInfo(string $file): array
    {
        $content = file_get_contents($file);
        if (!is_string($content) || $content === '') {
            return ['namespace' => null, 'symbolType' => null, 'symbol' => null, 'fqcn' => null];
        }

        $namespace = null;
        if (preg_match('/^\s*namespace\s+([^;\s]+)\s*;/m', $content, $m) === 1) {
            $namespace = trim($m[1]);
        }

        $symbolType = null;
        $symbol = null;
        if (preg_match('/^\s*(final\s+)?(abstract\s+)?(class|interface|trait|enum)\s+([A-Za-z_][A-Za-z0-9_]*)/m', $content, $m) === 1) {
            $symbolType = $m[3];
            $symbol = $m[4];
        }

        $fqcn = null;
        if ($namespace !== null && $symbol !== null) {
            $fqcn = $namespace . '\\' . $symbol;
        }

        return ['namespace' => $namespace, 'symbolType' => $symbolType, 'symbol' => $symbol, 'fqcn' => $fqcn];
    }

    /** @return array{layer: string, subdomain: string|null, isSourceDomain: bool} */
    private static function detectLayerFromPath(string $relPath): array
    {
        $norm = str_replace('\\', '/', $relPath);

        $isSourceDomain = false;
        if (str_starts_with($norm, 'SourceDomain/') || str_starts_with($norm, 'src/SourceDomain/')) {
            $isSourceDomain = true;
        }

        $parts = explode('/', $norm);
        $layer = null;
        $subdomain = null;

        if (count($parts) >= 2 && $parts[0] === 'src') {
            $layer = $parts[1];
            $subdomain = $parts[2] ?? null;
        }

        if ($isSourceDomain) {
            $layer = 'SourceDomain';
            $subdomain = $parts[1] ?? null;
        }

        return [
            'layer' => $layer ?? 'unknown',
            'subdomain' => $subdomain,
            'isSourceDomain' => $isSourceDomain,
        ];
    }

    /** @return array{missing: bool, expected: string|null} */
    private static function mirrorCheck(string $relPath): array
    {
        $norm = str_replace('\\', '/', $relPath);
        if (!str_starts_with($norm, 'src/')) {
            return ['missing' => false, 'expected' => null];
        }

        $parts = explode('/', $norm);
        $layer = $parts[1] ?? null;
        $subdomain = $parts[2] ?? null;
        $file = $parts[count($parts) - 1] ?? null;

        if ($layer === null || $subdomain === null || $file === null) {
            return ['missing' => false, 'expected' => null];
        }

        $layerMap = [
            'Service' => 'ServiceInterface',
            'Repository' => 'RepositoryInterface',
            'Controller' => 'ControllerInterface',
            'Domain' => 'DomainInterface',
        ];

        if (!isset($layerMap[$layer])) {
            return ['missing' => false, 'expected' => null];
        }

        $expectedPath = 'src/' . $layerMap[$layer] . '/' . $subdomain . '/' . preg_replace('/\.php$/', 'Interface.php', $file);

        $root = self::findRepoRoot();
        $abs = $root === null ? null : ($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $expectedPath));
        if ($abs !== null && is_file($abs)) {
            return ['missing' => false, 'expected' => $expectedPath];
        }

        return ['missing' => true, 'expected' => $expectedPath];
    }

    /** @return array{target_layer: string|null, target_path: string|null, reason: string} */
    private static function guessTargetPath(string $relPath, array $info): array
    {
        $norm = str_replace('\\', '/', $relPath);

        if (!str_starts_with($norm, 'SourceDomain/') && !str_starts_with($norm, 'src/SourceDomain/')) {
            return ['target_layer' => null, 'target_path' => null, 'reason' => 'not_source_domain'];
        }

        $file = basename($norm);
        $subdomain = null;
        $parts = explode('/', $norm);
        if (str_starts_with($norm, 'SourceDomain/')) {
            $subdomain = $parts[1] ?? null;
        }
        if (str_starts_with($norm, 'src/SourceDomain/')) {
            $subdomain = $parts[2] ?? null;
        }
        $subdomain = $subdomain ?: 'Analytics';

        $layer = 'Domain';
        $reason = 'fallback';

        if (str_ends_with($file, 'Controller.php')) {
            $layer = 'Controller';
            $reason = 'suffix_controller';
        } elseif (str_ends_with($file, 'Service.php')) {
            $layer = 'Service';
            $reason = 'suffix_service';
        } elseif (str_ends_with($file, 'Repository.php')) {
            $layer = 'Repository';
            $reason = 'suffix_repository';
        } elseif (str_ends_with($file, 'Entity.php')) {
            $layer = 'Entity';
            $reason = 'suffix_entity';
        } elseif (str_ends_with($file, 'Command.php')) {
            $layer = 'Command';
            $reason = 'suffix_command';
        } elseif (str_contains((string) ($info['namespace'] ?? ''), 'ValueObject')) {
            $layer = 'ValueObject';
            $reason = 'namespace_value_object';
        }

        $targetPath = 'src/' . $layer . '/' . $subdomain . '/' . $file;

        return ['target_layer' => $layer, 'target_path' => $targetPath, 'reason' => $reason];
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

    private static function renderDuplicateCsv(array $rows): string
    {
        $out = "basename,count,paths\n";
        foreach ($rows as $row) {
            $basename = str_replace('"', '""', $row['basename']);
            $paths = str_replace('"', '""', $row['paths']);
            $out .= '"' . $basename . '",' . $row['count'] . ',"' . $paths . "\n";
        }

        return $out;
    }

    /** @return array<string, string> */
    private static function parseArgv(array $argv): array
    {
        $out = [];
        foreach ($argv as $arg) {
            if (!is_string($arg) || !str_starts_with($arg, '--')) {
                continue;
            }
            $arg = substr($arg, 2);
            $kv = explode('=', $arg, 2);
            if (count($kv) === 2) {
                $out[$kv[0]] = $kv[1];
            }
        }

        return $out;
    }

    private static function findRepoRoot(): ?string
    {
        $dir = getcwd();
        if (!is_string($dir) || $dir === '') {
            return null;
        }

        $dir = realpath($dir) ?: $dir;

        for ($i = 0; $i < 8; $i++) {
            if (is_file($dir . DIRECTORY_SEPARATOR . 'composer.json')) {
                return $dir;
            }
            $parent = dirname($dir);
            if ($parent === $dir) {
                break;
            }
            $dir = $parent;
        }

        return null;
    }
}

exit(AnalyticsScanLayerMap::main($argv));
