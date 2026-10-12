<?php

declare(strict_types=1);

$publicDir = dirname(__DIR__, 2).'/public';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = is_string($path) && '' !== $path ? $path : '/';
$candidate = $publicDir.$path;

if ('/qa/dashboard-renderer' === $path) {
    require dirname(__DIR__, 2).'/vendor/autoload.php';

    $correlationIds = new class implements \App\Analysing\ProviderInterface\Http\AnalyticsRequestCorrelationIdProviderInterface {
        public function initialize(\Symfony\Component\HttpFoundation\Request $request): string
        {
            return 'qa-dashboard-correlation';
        }

        public function current(): string
        {
            return 'qa-dashboard-correlation';
        }
    };
    $vendorContext = new class implements \App\Analysing\ServiceInterface\Http\AnalyticsVendorContextInterface {
        public function set(string $vendor): void
        {
        }

        public function current(): string
        {
            return 'qa-vendor';
        }
    };
    $renderer = new \App\Analysing\Service\AnalyticsDashboardHtmlRenderer($correlationIds, $vendorContext);

    header('Content-Type: text/html; charset=UTF-8');
    echo $renderer->renderDashboard(
        ['gross_minor' => 245000, 'net_minor' => 183750, 'margin_pct' => 75.0, 'days' => 3],
        [
            ['date' => '2026-10-01', 'gross_minor' => 75000, 'net_minor' => 56250],
            ['date' => '2026-10-02', 'gross_minor' => 80000, 'net_minor' => 60000],
            ['date' => '2026-10-03', 'gross_minor' => 90000, 'net_minor' => 67500],
        ],
        [
            ['vendor_id' => 101, 'gross_minor' => 145000, 'net_minor' => 108750, 'margin_pct' => 75.0],
            ['vendor_id' => 202, 'gross_minor' => 100000, 'net_minor' => 75000, 'margin_pct' => 75.0],
        ],
        ['vendorId' => null, 'currency' => 'USD', 'from' => '2026-10-01', 'to' => '2026-10-03'],
    );

    return true;
}

if ('/' !== $path && is_file($candidate)) {
    return false;
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = $publicDir.'/index.php';

require $publicDir.'/index.php';

return true;
