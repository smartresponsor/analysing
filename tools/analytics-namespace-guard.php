<?php

/*
Marketing America Corp. Oleksandr Tishchenko
Author: Oleksandr Tishchenko <dev@highhopesamerica.com>
Owner: Marketing America Corp
*/

declare(strict_types=1);

final class AnalyticsNamespaceGuard
{
    private string $rootDir;

    public function __construct(string $rootDir)
    {
        $this->rootDir = rtrim($rootDir, "/");
    }

    /**
     * @return array{targetPrefix:string,allowedRoot:array<int,string>,bannedPrefix:array<int,string>}
     */
    public function loadConfig(): array
    {
        $path = $this->rootDir . '/config/guard/analytics-namespace-guard.json';
        if (!is_file($path)) {
            return [
                'targetPrefix' => 'App\\Analysing\\',
                'allowedRoot' => ['App'],
                'bannedPrefix' => ['SmartResponsor\\', 'Analytics\\'],
            ];
        }

        $raw = (string) file_get_contents($path);
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            throw new RuntimeException('analytics-analytics-namespace-guard.json is not valid JSON');
        }

        return [
            'targetPrefix' => (string) ($data['targetPrefix'] ?? 'App\\Analysing\\'),
            'allowedRoot' => array_values(array_map('strval', (array) ($data['allowedRoot'] ?? []))),
            'bannedPrefix' => array_values(array_map('strval', (array) ($data['bannedPrefix'] ?? []))),
        ];
    }

    /**
     * @return array<int,string>
     */
    public function listPhpFile(): array
    {
        $src = $this->rootDir . '/src';
        if (!is_dir($src)) {
            throw new RuntimeException('src directory not found');
        }

        $out = [];
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if (!$f->isFile()) {
                continue;
            }
            $path = $f->getPathname();
            if (str_ends_with($path, '.php')) {
                $out[] = $path;
            }
        }

        sort($out);
        return $out;
    }

    public function run(string $mode): int
    {
        $cfg = $this->loadConfig();
        $targetPrefix = $cfg['targetPrefix'];
        $allowedRoot = $cfg['allowedRoot'];
        $bannedPrefix = $cfg['bannedPrefix'];

        $files = $this->listPhpFile();

        $rootCount = [];
        $prefixCount = [];
        $nonCanonical = [];
        $bannedHit = [];
        $unknownRoot = [];

        foreach ($files as $file) {
            $ns = $this->readNamespace($file);
            if ($ns === null) {
                continue;
            }

            $root = $this->rootOf($ns);
            $rootCount[$root] = ($rootCount[$root] ?? 0) + 1;
            $prefixCount[$ns] = ($prefixCount[$ns] ?? 0) + 1;

            if (!in_array($root, $allowedRoot, true)) {
                $unknownRoot[$file] = $ns;
            }

            foreach ($bannedPrefix as $ban) {
                if (str_starts_with($ns, $ban)) {
                    $bannedHit[$file] = $ns;
                    break;
                }
            }

            if (!str_starts_with($ns, $targetPrefix)) {
                $nonCanonical[$file] = $ns;
            }
        }

        ksort($rootCount);
        arsort($prefixCount);

        $this->printLine('Analytics namespace guard');
        $this->printLine('mode=' . $mode);
        $this->printLine('targetPrefix=' . $targetPrefix);
        $this->printLine('phpFile=' . count($files));
        $this->printLine('');

        $this->printLine('root summary');
        foreach ($rootCount as $root => $cnt) {
            $this->printLine('  ' . $root . ': ' . $cnt);
        }
        $this->printLine('');

        $this->printLine('top namespace');
        $i = 0;
        foreach ($prefixCount as $ns => $cnt) {
            $i++;
            $this->printLine('  ' . $cnt . '  ' . $ns);
            if ($i >= 20) {
                break;
            }
        }
        $this->printLine('');

        $this->printLine('nonCanonical=' . count($nonCanonical));
        $this->printLine('banned=' . count($bannedHit));
        $this->printLine('unknownRoot=' . count($unknownRoot));

        if ($mode === 'report') {
            $this->printLine('');
            $this->printLine('banned sample');
            $this->printSample($bannedHit);
            $this->printLine('');
            $this->printLine('nonCanonical sample');
            $this->printSample($nonCanonical);
            $this->printLine('');
            $this->printLine('unknownRoot sample');
            $this->printSample($unknownRoot);
            return 0;
        }

        if ($mode === 'enforce') {
            if (count($bannedHit) > 0) {
                $this->printLine('ERROR banned namespaces found');
                return 2;
            }
            if (count($unknownRoot) > 0) {
                $this->printLine('ERROR unknown root namespaces found');
                return 3;
            }
            return 0;
        }

        $this->printLine('ERROR unknown mode');
        return 1;
    }

    private function printSample(array $map): void
    {
        $i = 0;
        foreach ($map as $file => $ns) {
            $this->printLine('  ' . $this->rel($file) . ' => ' . $ns);
            $i++;
            if ($i >= 15) {
                break;
            }
        }
        if (count($map) > 15) {
            $this->printLine('  ...');
        }
    }

    private function rel(string $abs): string
    {
        $root = $this->rootDir;
        if (str_starts_with($abs, $root . '/')) {
            return substr($abs, strlen($root) + 1);
        }
        return $abs;
    }

    private function readNamespace(string $file): ?string
    {
        $fh = fopen($file, 'rb');
        if ($fh === false) {
            return null;
        }

        $max = 60;
        while (!feof($fh) && $max > 0) {
            $max--;
            $line = fgets($fh);
            if ($line === false) {
                break;
            }
            $line = trim($line);
            if (str_starts_with($line, 'namespace ')) {
                $line = substr($line, strlen('namespace '));
                $line = rtrim($line, ';');
                $line = trim($line);
                fclose($fh);
                return $line;
            }
        }

        fclose($fh);
        return null;
    }

    private function rootOf(string $namespace): string
    {
        $pos = strpos($namespace, '\\');
        if ($pos === false) {
            return $namespace;
        }
        return substr($namespace, 0, $pos);
    }

    private function printLine(string $line): void
    {
        echo $line . PHP_EOL;
    }
}

$mode = 'report';
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--mode=')) {
        $mode = substr($arg, strlen('--mode='));
    }
}

$guard = new AnalyticsNamespaceGuard(__DIR__ . '/..');
exit($guard->run($mode));
