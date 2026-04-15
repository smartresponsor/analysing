<?php

declare(strict_types=1);

namespace App\Service\Analytics;

use App\ServiceInterface\Analytics\DashboardHtmlRendererInterface;
use App\ServiceInterface\Http\RequestCorrelationIdProviderInterface;
use App\ServiceInterface\Http\TenantContextInterface;

final readonly class DashboardHtmlRenderer implements DashboardHtmlRendererInterface
{
    public function __construct(
        private RequestCorrelationIdProviderInterface $correlationIds,
        private TenantContextInterface $tenantContext,
    ) {
    }

    /**
     * @param array{gross_minor:int, net_minor:int, margin_pct:float|int, days:int}            $kpi
     * @param list<array{date:string, gross_minor:int, net_minor:int}>                         $series
     * @param list<array{vendor_id:int, gross_minor:int, net_minor:int, margin_pct:float|int}> $top
     * @param array<string,int|string|null>                                                    $params
     *
     * @return string
     */
    public function renderDashboard(array $kpi, array $series, array $top, array $params): string
    {
        $correlationId = $this->correlationIds->current();
        $tenant = $this->tenantContext->current();
        $generatedAt = gmdate(DATE_ATOM);

        $filters = [
            'Vendor' => null !== $params['vendorId'] ? (string) $params['vendorId'] : 'all',
            'Currency' => $params['currency'] ?? 'all',
            'From' => $params['from'] ?? 'default window',
            'To' => $params['to'] ?? 'now',
        ];

        $html = [];
        $html[] = '<!DOCTYPE html>';
        $html[] = '<html lang="en">';
        $html[] = '<head>';
        $html[] = '  <meta charset="UTF-8">';
        $html[] = '  <meta name="viewport" content="width=device-width, initial-scale=1.0">';
        $html[] = '  <title>Analytics Dashboard</title>';
        $html[] = '  <style>';
        $html[] = 'body{font-family:Arial,sans-serif;background:#0f172a;color:#e2e8f0;margin:0;padding:24px;}';
        $html[] = '.shell{max-width:1200px;margin:0 auto;display:grid;gap:20px;}';
        $html[] = '.hero,.panel,.card{background:#111827;border:1px solid #334155;border-radius:16px;box-shadow:0 12px 24px rgba(15,23,42,.25);}';
        $html[] = '.hero{padding:24px;}';
        $html[] = '.hero h1{margin:0 0 8px;font-size:28px;}';
        $html[] = '.meta{display:flex;flex-wrap:wrap;gap:12px;font-size:13px;color:#94a3b8;margin-top:12px;}';
        $html[] = '.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;}';
        $html[] = '.card{padding:18px;}';
        $html[] = '.label{font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:#94a3b8;}';
        $html[] = '.value{font-size:30px;font-weight:700;margin-top:8px;}';
        $html[] = '.panel{padding:20px;}';
        $html[] = 'table{width:100%;border-collapse:collapse;margin-top:12px;}';
        $html[] = 'th,td{text-align:left;padding:10px 12px;border-bottom:1px solid #1f2937;}';
        $html[] = 'th{font-size:12px;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;}';
        $html[] = 'caption{text-align:left;font-weight:700;margin-bottom:8px;font-size:18px;color:#f8fafc;}';
        $html[] = 'code{background:#0b1220;border:1px solid #1e293b;border-radius:8px;padding:2px 6px;color:#93c5fd;}';
        $html[] = 'pre{overflow:auto;background:#0b1220;border:1px solid #1e293b;border-radius:12px;padding:16px;color:#cbd5e1;}';
        $html[] = 'a{color:#93c5fd;text-decoration:none;}';
        $html[] = 'a:hover{text-decoration:underline;}';
        $html[] = '  </style>';
        $html[] = '</head>';
        $html[] = '<body>';
        $html[] = '  <main class="shell">';
        $html[] = '    <section class="hero">';
        $html[] = '      <h1>Analytics dashboard</h1>';
        $html[] = '      <p>HTML-first operational view for the analytics component. Add <code>?format=json</code> or send <code>Accept: application/json</code> for machine-friendly output.</p>';
        $html[] = '      <div class="meta">';
        $html[] = '        <span>Tenant: <strong>'.$this->escape($tenant).'</strong></span>';
        $html[] = '        <span>Correlation ID: <strong>'.$this->escape($correlationId).'</strong></span>';
        $html[] = '        <span>Generated at: <strong>'.$this->escape($generatedAt).'</strong></span>';
        foreach ($filters as $label => $value) {
            $html[] = '        <span>'.$this->escape((string) $label).': <strong>'.$this->escape((string) $value).'</strong></span>';
        }
        $html[] = '      </div>';
        $html[] = '    </section>';

        $html[] = '    <section class="grid">';
        $html[] = $this->metricCard('Gross', $this->formatMinor($kpi['gross_minor']));
        $html[] = $this->metricCard('Net', $this->formatMinor($kpi['net_minor']));
        $html[] = $this->metricCard('Margin %', $this->formatPercent((float) $kpi['margin_pct']));
        $html[] = $this->metricCard('Days', (string) $kpi['days']);
        $html[] = '    </section>';

        $html[] = '    <section class="panel">';
        $html[] = '      <table>';
        $html[] = '        <caption>Timeseries</caption>';
        $html[] = '        <thead><tr><th>Date</th><th>Gross</th><th>Net</th></tr></thead>';
        $html[] = '        <tbody>';
        foreach ($series as $row) {
            $html[] = '          <tr><td>'.$this->escape($row['date']).'</td><td>'.$this->escape($this->formatMinor($row['gross_minor'])).'</td><td>'.$this->escape($this->formatMinor($row['net_minor'])).'</td></tr>';
        }
        if ([] === $series) {
            $html[] = '          <tr><td colspan="3">No timeseries rows available.</td></tr>';
        }
        $html[] = '        </tbody>';
        $html[] = '      </table>';
        $html[] = '    </section>';

        $html[] = '    <section class="panel">';
        $html[] = '      <table>';
        $html[] = '        <caption>Top vendors</caption>';
        $html[] = '        <thead><tr><th>Vendor ID</th><th>Gross</th><th>Net</th><th>Margin %</th></tr></thead>';
        $html[] = '        <tbody>';
        foreach ($top as $row) {
            $html[] = '          <tr><td>'.$this->escape((string) $row['vendor_id']).'</td><td>'.$this->escape($this->formatMinor($row['gross_minor'])).'</td><td>'.$this->escape($this->formatMinor($row['net_minor'])).'</td><td>'.$this->escape($this->formatPercent((float) $row['margin_pct'])).'</td></tr>';
        }
        if ([] === $top) {
            $html[] = '          <tr><td colspan="4">No vendor rows available.</td></tr>';
        }
        $html[] = '        </tbody>';
        $html[] = '      </table>';
        $html[] = '    </section>';

        $html[] = '    <section class="panel">';
        $html[] = '      <table>';
        $html[] = '        <caption>Request parameters</caption>';
        $html[] = '        <tbody>';
        foreach ($params as $key => $value) {
            $html[] = '          <tr><th>'.$this->escape($key).'</th><td>'.$this->escape(null !== $value ? (string) $value : 'null').'</td></tr>';
        }
        $html[] = '        </tbody>';
        $html[] = '      </table>';
        $html[] = '    </section>';

        try {
            $payloadJson = json_encode([
                'kpi' => $kpi,
                'series' => $series,
                'top' => $top,
                'params' => $params,
                'tenant' => $tenant,
                'correlation_id' => $correlationId,
                'generated_at' => $generatedAt,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $payloadJson = '{"error":"dashboard payload unavailable"}';
        }

        $html[] = '    <section class="panel">';
        $html[] = '      <caption style="display:block;font-weight:700;font-size:18px;color:#f8fafc;margin-bottom:8px;">Embedded payload</caption>';
        $html[] = '      <pre>'.$this->escape($payloadJson).'</pre>';
        $html[] = '    </section>';
        $html[] = '  </main>';
        $html[] = '</body>';
        $html[] = '</html>';

        return implode("\n", $html);
    }

    public function renderError(string $title, string $message, int $status): string
    {
        $correlationId = $this->correlationIds->current();
        $tenant = $this->tenantContext->current();
        $generatedAt = gmdate(DATE_ATOM);

        return implode("\n", [
            '<!DOCTYPE html>',
            '<html lang="en">',
            '<head>',
            '  <meta charset="UTF-8">',
            '  <meta name="viewport" content="width=device-width, initial-scale=1.0">',
            '  <title>'.$this->escape($title).'</title>',
            '  <style>body{font-family:Arial,sans-serif;background:#0f172a;color:#e2e8f0;margin:0;padding:24px;}main{max-width:720px;margin:48px auto;background:#111827;border:1px solid #334155;border-radius:16px;padding:24px;}h1{margin-top:0;}p,li{line-height:1.6;}code{background:#0b1220;border:1px solid #1e293b;border-radius:8px;padding:2px 6px;color:#93c5fd;}</style>',
            '</head>',
            '<body>',
            '  <main>',
            '    <h1>'.$this->escape($title).'</h1>',
            '    <p>'.$this->escape($message).'</p>',
            '    <ul>',
            '      <li>Status: <strong>'.$this->escape((string) $status).'</strong></li>',
            '      <li>Tenant: <strong>'.$this->escape($tenant).'</strong></li>',
            '      <li>Correlation ID: <strong>'.$this->escape($correlationId).'</strong></li>',
            '      <li>Generated at: <strong>'.$this->escape($generatedAt).'</strong></li>',
            '    </ul>',
            '    <p>Append <code>?format=json</code> to receive the machine-readable analytics envelope for this route.</p>',
            '  </main>',
            '</body>',
            '</html>',
        ]);
    }

    private function metricCard(string $label, string $value): string
    {
        return '<article class="card"><div class="label">'.$this->escape((string) $label).'</div><div class="value">'.$this->escape((string) $value).'</div></article>';
    }

    private function formatMinor(int $amount): string
    {
        return number_format($amount / 100, 2);
    }

    private function formatPercent(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.').'%';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
