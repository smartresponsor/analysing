<?php

declare(strict_types=1);

if (!isset($_SERVER['APP_ENV']) && !isset($_ENV['APP_ENV'])) {
    $_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'dev';
}

if (!isset($_SERVER['APP_DEBUG']) && !isset($_ENV['APP_DEBUG'])) {
    $_SERVER['APP_DEBUG'] = $_ENV['APP_DEBUG'] = '1';
}

if (!isset($_SERVER['APP_SECRET']) && !isset($_ENV['APP_SECRET'])) {
    $_SERVER['APP_SECRET'] = $_ENV['APP_SECRET'] = 'analytics-dev-secret';
}
