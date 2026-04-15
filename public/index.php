<?php

declare(strict_types=1);

use App\Kernel;
use Symfony\Component\HttpFoundation\Request;

require dirname(__DIR__).'/vendor/autoload.php';
require dirname(__DIR__).'/config/bootstrap.php';

// analytics-oct30-final-winners
$kernel = new Kernel($_SERVER['APP_ENV'] ?? 'dev', (bool) ($_SERVER['APP_DEBUG'] ?? false));
$request = Request::createFromGlobals();
try {
    $response = $kernel->handle($request);
    $response->send();
    $kernel->terminate($request, $response);
} catch (Throwable $exception) {
    http_response_code(500);
    echo 'Analytics application failed.';
    error_log($exception->getMessage());
}
