<?php
/**
 * Marketing America Corp. Oleksandr Tishchenko
 *
 * Guard: forbids "InterfaceInterface" style duplicates.
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$src = $root . DIRECTORY_SEPARATOR . 'src';

if (!is_dir($src)) {
    fwrite(STDERR, "src/ not found\n");
    exit(2);
}

$bad = [];

$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src));
foreach ($it as $file) {
    if (!$file->isFile()) {
        continue;
    }

    $path = $file->getPathname();
    if (substr($path, -4) !== '.php') {
        continue;
    }

    $baseName = $file->getBasename();
    if (strpos($baseName, 'InterfaceInterface') !== false) {
        $bad[] = $path;
        continue;
    }

    $content = file_get_contents($path);
    if ($content === false) {
        continue;
    }

    if (preg_match('/\bInterfaceInterface\b/', $content) === 1) {
        $bad[] = $path;
        continue;
    }
}

if (count($bad) > 0) {
    fwrite(STDERR, "InterfaceInterface anomalies found:\n");
    foreach ($bad as $p) {
        fwrite(STDERR, "- " . $p . "\n");
    }
    exit(1);
}

fwrite(STDOUT, "OK: no InterfaceInterface anomalies\n");
exit(0);
