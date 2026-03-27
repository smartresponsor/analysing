<?php

// Marketing America Corp. Oleksandr Tishchenko

declare(strict_types=1);

$root = dirname(__DIR__);
$src = $root . DIRECTORY_SEPARATOR . 'src';
$reportDir = $root . DIRECTORY_SEPARATOR . 'report';
$apply = in_array('--apply', $argv, true);

if (!is_dir($src)) {
    fwrite(STDERR, "src/ not found\n");
    exit(2);
}

if (!is_dir($reportDir)) {
    @mkdir($reportDir, 0777, true);
}

$toRemove = [];

$it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS)
);

foreach ($it as $fileinfo) {
    if (!$fileinfo->isFile()) {
        continue;
    }
    $name = $fileinfo->getFilename();
    if (substr($name, -4) !== '.php') {
        continue;
    }
    if (strpos($name, 'InterfaceInterface') === false) {
        continue;
    }

    $full = $fileinfo->getPathname();
    $rel = str_replace($root . DIRECTORY_SEPARATOR, '', $full);
    $toRemove[] = $rel;

    if ($apply) {
        @unlink($full);
    }
}

sort($toRemove);

$report = $reportDir . DIRECTORY_SEPARATOR . 'analytics-interfaceinterface-purge.txt';
$lines = [];
$lines[] = "apply=" . ($apply ? "true" : "false");
$lines[] = "remove_count=" . (string)count($toRemove);
$lines[] = "remove:";
foreach ($toRemove as $rel) {
    $lines[] = "- " . $rel;
}
$lines[] = "";
file_put_contents($report, implode(PHP_EOL, $lines));

if (!$apply) {
    fwrite(STDOUT, "Dry run. To apply: php tools/analytics-interfaceinterface-purge.php --apply\n");
}
fwrite(STDOUT, "Remove candidates: " . count($toRemove) . "\n");
fwrite(STDOUT, "Report: report/analytics-interfaceinterface-purge.txt\n");

exit(0);
