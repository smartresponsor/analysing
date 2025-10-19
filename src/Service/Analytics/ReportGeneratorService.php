<?php declare(strict_types=1);
namespace App\Service\Analytics;
final class ReportGeneratorService
{
    public function generateCsv(array $aggregates): string
    {
        $fh = fopen('php://temp', 'r+');
        fputcsv($fh, ['metric','total','from','to']);
        foreach ($aggregates as $row) {
            fputcsv($fh, [
                $row['metric'] ?? '',
                (string)($row['total'] ?? 0),
                isset($row['from']) ? ($row['from'] instanceof \DateTimeInterface ? $row['from']->format('c') : (string)$row['from']) : '',
                isset($row['to']) ? ($row['to'] instanceof \DateTimeInterface ? $row['to']->format('c') : (string)$row['to']) : '',
            ]);
        }
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);
        return $csv === false ? '' : $csv;
    }
}
