<?php

// Marketing America Corp. Oleksandr Tishchenko

declare(strict_types=1);

$root = dirname(__DIR__);
$configDir = $root . DIRECTORY_SEPARATOR . 'config';
$reportDir = $root . DIRECTORY_SEPARATOR . 'report';
$apply = in_array('--apply', $argv, true);

if (!is_dir($configDir)) {
    fwrite(STDERR, "config/ not found\n");
    exit(2);
}

if (!is_dir($reportDir)) {
    @mkdir($reportDir, 0777, true);
}

$removed = [];
$kept = [];
$it = new DirectoryIterator($configDir);

foreach ($it as $fileinfo) {
    if ($fileinfo->isDot()) {
        continue;
    }
    if ($fileinfo->isDir()) {
        continue;
    }

    $name = $fileinfo->getFilename();

    // Keep known config files.
    if (preg_match('/\.(ya?ml|xml|php|json|neon|dist|md|lock)$/i', $name) === 1) {
        $kept[] = $name;
        continue;
    }

    // Keep canonical dotfiles and placeholders.
    if (in_array($name, ['.keep', '.gitignore', '.gitattributes', '.editorconfig'], true)) {
        $kept[] = $name;
        continue;
    }

    // Target: extensionless sha/hex cache-like files.
    if (preg_match('/^[0-9a-f]{32,64}$/i', $name) === 1) {
        $full = $fileinfo->getPathname();
        $removed[] = $name;

        if ($apply) {
            @unlink($full);
        }
        continue;
    }

    // Unknown file in config root: report it, do not delete automatically.
    $kept[] = $name;
}

sort($removed);
sort($kept);

$report = $reportDir . DIRECTORY_SEPARATOR . 'analytics-config-garbage.txt';
$lines = [];
$lines[] = "apply=" . ($apply ? "true" : "false");
$lines[] = "removed_count=" . (string)count($removed);
$lines[] = "removed:";
foreach ($removed as $name) {
    $lines[] = "- " . $name;
}
$lines[] = "kept_count=" . (string)count($kept);
$lines[] = "kept:";
foreach ($kept as $name) {
    $lines[] = "- " . $name;
}
$lines[] = "";

file_put_contents($report, implode(PHP_EOL, $lines));

if (!$apply) {
    fwrite(STDOUT, "Dry run. To apply: php tools/analytics-config-garbage-clean.php --apply\n");
}
fwrite(STDOUT, "Removed candidates: " . count($removed) . "\n");
fwrite(STDOUT, "Report: report/analytics-config-garbage.txt\n");

exit(0);
