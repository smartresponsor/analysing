<?php

declare(strict_types=1);

namespace App\Analysing\DTO\Config;

final class AnalyticsEnvironmentConfigDataDTO
{
    public string $clickhouseBase = 'http://127.0.0.1:8123';
    public string $clickhouseUser = 'default';
    public string $idempotencyEnabled = '1';
    public string $idempotencyRequired = '0';
    public string $authRequired = '0';
    public string $authPublicRead = '1';
    public string $rateLimitEnabled = '1';
    public string $rateLimitWindowSeconds = '60';
    public string $rateLimitDefaultWriteLimit = '60';
}
