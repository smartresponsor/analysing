<?php
declare(strict_types=1);

namespace App\Service\Analytics;

use Doctrine\DBAL\Connection;
use App\DTO\Analytics\KpiRequest;
use Throwable;

final class DashboardService
{
    public function __construct(private readonly Connection $db) {}

    public function kpi(KpiRequest $req): array
    {
        $where = [];
        $params = [];

        if ($req->vendorId !== null) { $where[] = 'vendor_id = :v'; $params['v'] = $req->vendorId; }
        if ($req->currency !== null) { $where[] = 'currency = :c'; $params['c'] = strtoupper($req->currency); }
        if ($req->from !== null) { $where[] = 'date >= :from'; $params['from'] = $req->from; }
        if ($req->to !== null) { $where[] = 'date <= :to'; $params['to'] = $req->to; }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $sql = "SELECT COALESCE(SUM(gross_minor),0) AS gross,
                       COALESCE(SUM(net_minor),0)   AS net,
                       COUNT(*) AS days
                  FROM metric_snapshot
                $whereSql";

        try {
            $row = $this->db->fetchAssociative($sql, $params) ?: ['gross'=>0,'net'=>0,'days'=>0];
        } catch (Throwable $e) {
            $row = ['gross'=>0,'net'=>0,'days'=>0];
        }

        $gross = (int)$row['gross'];
        $net   = (int)$row['net'];
        $margin = $gross > 0 ? round(($net / $gross) * 100, 2) : 0;

        return [
            'gross_minor' => $gross,
            'net_minor' => $net,
            'margin_pct' => $margin,
            'days' => (int)$row['days'],
        ];
    }

    public function timeseries(KpiRequest $req): array
    {
        $where = [];
        $params = [];

        if ($req->vendorId !== null) { $where[] = 'vendor_id = :v'; $params['v'] = $req->vendorId; }
        if ($req->currency !== null) { $where[] = 'currency = :c'; $params['c'] = strtoupper($req->currency); }
        if ($req->from !== null) { $where[] = 'date >= :from'; $params['from'] = $req->from; }
        if ($req->to !== null) { $where[] = 'date <= :to'; $params['to'] = $req->to; }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $sql = "SELECT DATE_FORMAT(date, '%Y-%m-%d') as d, SUM(gross_minor) as gross, SUM(net_minor) as net
                  FROM metric_snapshot
                $whereSql
              GROUP BY d
              ORDER BY d ASC";
        try {
            $rows = $this->db->fetchAllAssociative($sql, $params);
        } catch (Throwable $e) {
            $rows = [];
        }

        return array_map(fn($r) => ['date' => $r['d'], 'gross_minor' => (int)$r['gross'], 'net_minor' => (int)$r['net']], $rows);
    }

    public function byVendor(?string $currency = null, ?string $from = null, ?string $to = null): array
    {
        $where = [];
        $params = [];
        if ($currency !== null) { $where[] = 'currency = :c'; $params['c'] = strtoupper($currency); }
        if ($from !== null) { $where[] = 'date >= :from'; $params['from'] = $from; }
        if ($to !== null) { $where[] = 'date <= :to'; $params['to'] = $to; }
        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $sql = "SELECT vendor_id, SUM(gross_minor) as gross, SUM(net_minor) as net
                  FROM metric_snapshot
                $whereSql
              GROUP BY vendor_id
              ORDER BY gross DESC";

        try {
            $rows = $this->db->fetchAllAssociative($sql, $params);
        } catch (Throwable $e) {
            $rows = [];
        }

        return array_map(function ($r) {
            $gross = (int)$r['gross'];
            $net = (int)$r['net'];
            $margin = $gross > 0 ? round(($net / $gross) * 100, 2) : 0.0;
            return [
                'vendor_id' => (int)$r['vendor_id'],
                'gross_minor' => $gross,
                'net_minor' => $net,
                'margin_pct' => $margin,
            ];
        }, $rows);
    }
}
