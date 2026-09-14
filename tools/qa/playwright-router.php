<?php

declare(strict_types=1);

$publicDir = dirname(__DIR__, 2).'/public';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = is_string($path) && '' !== $path ? $path : '/';
$candidate = $publicDir.$path;

if ('/' !== $path && is_file($candidate)) {
    return false;
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = $publicDir.'/index.php';

require $publicDir.'/index.php';

return true;
