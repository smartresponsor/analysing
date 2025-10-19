<?php declare(strict_types=1);
namespace App\Service\Analytics;
use App\Entity\Analytics\ExportJob;
use Doctrine\ORM\EntityManagerInterface;

final class ReportExporterService
{
    public function __construct(private readonly EntityManagerInterface $em) {}
    public function exportCsv(ExportJob $job, string $csv): void
    {
        $job->start(); $this->em->flush();
        try {
            $path = $job->getPayload()['path'] ?? null;
            if (!$path) { throw new \RuntimeException('Export path is not defined.'); }
            $dir = \dirname($path);
            if (!is_dir($dir)) { if (!@mkdir($dir, 0775, true) && !is_dir($dir)) { throw new \RuntimeException('Cannot create export dir: ' . $dir); } }
            if (@file_put_contents($path, $csv) === false) { throw new \RuntimeException('Failed to write CSV to ' . $path); }
            $job->done();
        } catch (\Throwable $e) { $job->fail($e->getMessage()); throw $e; }
        finally { $this->em->flush(); }
    }
}
