<?php

declare(strict_types=1);

namespace App\Analysing\Service\Doctrine;

use App\Analysing\Repository\AnalyticsStorageRepository;
use App\Analysing\ServiceInterface\Doctrine\AnalyticsStorageManagerInterface;

final readonly class AnalyticsStorageManager implements AnalyticsStorageManagerInterface
{
    public function __construct(private AnalyticsStorageRepository $repository)
    {
    }

    public function inspect(): array
    {
        return $this->repository->inspect();
    }

    public function prepare(bool $seed = false): array
    {
        return $this->repository->prepare($seed);
    }
}
