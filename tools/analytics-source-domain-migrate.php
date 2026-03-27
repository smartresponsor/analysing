<?php
declare(strict_types=1);

/*
 * Marketing America Corp. Oleksandr Tishchenko
 * Comments in English only. Singular naming.
 *
 * SourceDomain is not canonical. This tool detects SourceDomain candidates
 * and prints a relocation plan. Use --apply to execute file moves.
 */

final class AnalyticsSourceDomainMigrate
{
    private string $root;
    private bool $apply;

    public function __construct(string $root, bool $apply)
    {
        $this->root = rtrim($root, '/');
        $this->apply = $apply;
    }

    public function run(): int
    {
        $dirs = $this->findSourceDomainDir();
        if ($dirs === []) {
            echo "OK: SourceDomain directory not found. Nothing to migrate.\n";
            return 0;
        }

        $plan = [];
        foreach ($dirs as $dir) {
            $plan = array_merge($plan, $this->buildPlan($dir));
        }

        if ($plan === []) {
            echo "OK: No PHP files found under SourceDomain.\n";
            return 0;
        }

        $this->printPlan($plan);

        if (!$this->apply) {
            echo "DRY-RUN: Use --apply to execute the plan.\n";
            return 0;
        }

        $this->applyPlan($plan);
        echo "DONE: Migration applied.\n";
        return 0;
    }

    /** @return list<string> */
    private function findSourceDomainDir(): array
    {
        $dirs = [
            $this->root . '/SourceDomain',
            $this->root . '/src/SourceDomain',
            $this->root . '/src/Domain/SourceDomain',
        ];

        $out = [];
        foreach ($dirs as $d) {
            if (is_dir($d)) {
                $out[] = $d;
            }
        }
        return $out;
    }

    /**
     * @return list<array{from:string,to:string,rel:string}>
     */
    private function buildPlan(string $dir): array
    {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
        );

        $plan = [];
        foreach ($it as $file) {
            if (!$file->isFile()) {
                continue;
            }
            if (!str_ends_with($file->getFilename(), '.php')) {
                continue;
            }

            $from = $file->getPathname();
            $rel = ltrim(substr($from, strlen($this->root)), '/');

            $base = $file->getFilename();

            $targetDir = $this->root . '/src/Domain/Analytics';
            if (str_contains($base, 'Controller')) $targetDir = $this->root . '/src/Controller/Analytics';
            elseif (str_contains($base, 'Command')) $targetDir = $this->root . '/src/Command';
            elseif (str_contains($base, 'Repository')) $targetDir = $this->root . '/src/Repository/Analytics';
            elseif (str_contains($base, 'Service')) $targetDir = $this->root . '/src/Service/Analytics';
            elseif (str_contains($base, 'Entity')) $targetDir = $this->root . '/src/Entity/Analytics';

            $to = rtrim($targetDir, '/') . '/' . $base;

            $plan[] = ['from' => $from, 'to' => $to, 'rel' => $rel];
        }

        return $plan;
    }

    /**
     * @param list<array{from:string,to:string,rel:string}> $plan
     */
    private function printPlan(array $plan): void
    {
        echo "SourceDomain migration plan:\n";
        foreach ($plan as $p) {
            $toRel = ltrim(substr($p['to'], strlen($this->root)), '/');
            echo " - {$p['rel']} => {$toRel}\n";
        }
    }

    /**
     * @param list<array{from:string,to:string,rel:string}> $plan
     */
    private function applyPlan(array $plan): void
    {
        foreach ($plan as $p) {
            $toDir = dirname($p['to']);
            if (!is_dir($toDir)) {
                mkdir($toDir, 0775, true);
            }
            if (!rename($p['from'], $p['to'])) {
                throw new RuntimeException('Failed to move: ' . $p['from'] . ' -> ' . $p['to']);
            }
        }
    }
}

$apply = in_array('--apply', $argv, true);
$tool = new AnalyticsSourceDomainMigrate(getcwd(), $apply);
exit($tool->run());
