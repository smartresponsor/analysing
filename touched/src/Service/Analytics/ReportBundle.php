<?php
declare(strict_types=1);

namespace App\Service\Analytics;

use App\ServiceInterface\Analytics\ReportBundleInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

final class ReportBundle implements ReportBundleInterface
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function pack(array $datasets): array
    {
        $manifest = [];

        foreach ($datasets as $name => $rows) {
            $datasetName = trim((string) $name);
            if ($datasetName === '') {
                $this->logger->warning('Analytics report bundle rejected a dataset with an empty name.');
                throw new InvalidArgumentException('Dataset name must not be empty.');
            }

            if (!is_array($rows)) {
                $this->logger->warning('Analytics report bundle rejected a dataset with a non-array payload.', [
                    'dataset' => $datasetName,
                    'payload_type' => get_debug_type($rows),
                ]);
                throw new InvalidArgumentException('Dataset rows must be an array.');
            }

            $manifest[$datasetName] = ['rows' => count($rows)];
        }

        return $manifest;
    }
}
