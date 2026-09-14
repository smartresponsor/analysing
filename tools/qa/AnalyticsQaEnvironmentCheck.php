<?php

declare(strict_types=1);

$requiredExtensions = [
    'dom',
    'json',
    'libxml',
    'mbstring',
    'tokenizer',
    'xml',
    'xmlwriter',
];

$optionalExtensions = [
    'pdo',
    'pdo_sqlite',
];

$missingRequired = [];
foreach ($requiredExtensions as $extension) {
    if (!extension_loaded($extension)) {
        $missingRequired[] = $extension;
    }
}

$missingOptional = [];
foreach ($optionalExtensions as $extension) {
    if (!extension_loaded($extension)) {
        $missingOptional[] = $extension;
    }
}

fwrite(STDOUT, "Analytics QA environment preflight\n");
fwrite(STDOUT, str_repeat('=', 34)."\n");

if ($missingRequired === []) {
    fwrite(STDOUT, "Required PHPUnit extensions: OK\n");
} else {
    fwrite(STDOUT, "Missing required PHPUnit extensions: ".implode(', ', $missingRequired)."\n");
}

if ($missingOptional === []) {
    fwrite(STDOUT, "Optional analytics runtime extensions: OK\n");
} else {
    fwrite(STDOUT, "Missing optional analytics runtime extensions: ".implode(', ', $missingOptional)."\n");
}

if ($missingRequired !== []) {
    fwrite(STDERR, "Cannot run PHPUnit reliably until the required extensions are installed.\n");
    exit(1);
}

exit(0);
