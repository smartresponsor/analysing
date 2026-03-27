<?php

declare(strict_types=1);

namespace App\Service\Analytics;

use App\ServiceInterface\Analytics\ReportBundleInterface;
use Psr\Log\LoggerInterface;

final class ReportBundle implements ReportBundleInterface
{
    private const MAX_DATASETS = 64;
    private const MAX_COLUMNS = 256;
    private const MAX_DATASET_NAME_LENGTH = 128;
    private const MAX_ROWS_PER_DATASET = 10000;

    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function pack(array $datasets): array
    {
        if ([] === $datasets) {
            $this->logger->warning('Analytics report bundle rejected an empty dataset list.');
            throw new \InvalidArgumentException('Datasets must not be empty.');
        }

        if (count($datasets) > self::MAX_DATASETS) {
            $this->logger->warning('Analytics report bundle rejected too many datasets.', [
                'datasets' => count($datasets),
                'max_datasets' => self::MAX_DATASETS,
            ]);
            throw new \InvalidArgumentException('Dataset count exceeds the maximum allowed value.');
        }

        $manifest = [];

        foreach ($datasets as $name => $rows) {
            $datasetName = trim((string) $name);
            if ('' === $datasetName) {
                $this->logger->warning('Analytics report bundle rejected a dataset with an empty name.');
                throw new \InvalidArgumentException('Dataset name must not be empty.');
            }

            if (strlen($datasetName) > self::MAX_DATASET_NAME_LENGTH) {
                $this->logger->warning('Analytics report bundle rejected an overlong dataset name.', [
                    'dataset' => $datasetName,
                    'max_length' => self::MAX_DATASET_NAME_LENGTH,
                ]);
                throw new \InvalidArgumentException('Dataset name exceeds the maximum allowed length.');
            }

            if (isset($manifest[$datasetName])) {
                $this->logger->warning('Analytics report bundle rejected duplicate dataset names after normalization.', [
                    'dataset' => $datasetName,
                ]);
                throw new \InvalidArgumentException(sprintf('Dataset %s is duplicated.', $datasetName));
            }

            if (!is_array($rows)) {
                $this->logger->warning('Analytics report bundle rejected a dataset with a non-array payload.', [
                    'dataset' => $datasetName,
                    'payload_type' => get_debug_type($rows),
                ]);
                throw new \InvalidArgumentException('Dataset rows must be an array.');
            }

            if (count($rows) > self::MAX_ROWS_PER_DATASET) {
                $this->logger->warning('Analytics report bundle rejected a dataset with too many rows.', [
                    'dataset' => $datasetName,
                    'rows' => count($rows),
                    'max_rows' => self::MAX_ROWS_PER_DATASET,
                ]);
                throw new \InvalidArgumentException(sprintf('Dataset %s exceeds the maximum number of rows.', $datasetName));
            }

            $manifest[$datasetName] = $this->buildDatasetManifest($datasetName, $rows);
        }

        ksort($manifest);

        return $manifest;
    }

    /**
     * @param list<mixed>|array<array-key,mixed> $rows
     *
     * @return array{rows:int,columns:list<string>}
     */
    private function buildDatasetManifest(string $datasetName, array $rows): array
    {
        $columns = [];

        foreach ($rows as $index => $row) {
            if (!is_array($row)) {
                $this->logger->warning('Analytics report bundle rejected a dataset row with a non-array shape.', [
                    'dataset' => $datasetName,
                    'row_index' => $index,
                    'row_type' => get_debug_type($row),
                ]);
                throw new \InvalidArgumentException(sprintf('Dataset %s contains a non-array row.', $datasetName));
            }

            foreach ($row as $column => $_) {
                $normalizedColumn = trim((string) $column);
                if ('' === $normalizedColumn) {
                    $this->logger->warning('Analytics report bundle rejected a dataset row with an empty column name.', [
                        'dataset' => $datasetName,
                        'row_index' => $index,
                    ]);
                    throw new \InvalidArgumentException(sprintf('Dataset %s contains an empty column name.', $datasetName));
                }

                $columns[$normalizedColumn] = true;
                if (count($columns) > self::MAX_COLUMNS) {
                    $this->logger->warning('Analytics report bundle rejected a dataset with too many distinct columns.', [
                        'dataset' => $datasetName,
                        'max_columns' => self::MAX_COLUMNS,
                    ]);
                    throw new \InvalidArgumentException(sprintf('Dataset %s exceeds the maximum number of columns.', $datasetName));
                }
            }
        }

        $columnList = array_values(array_keys($columns));
        sort($columnList);

        return [
            'rows' => count($rows),
            'columns' => $columnList,
        ];
    }
}
