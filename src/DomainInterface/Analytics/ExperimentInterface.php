<?php

declare(strict_types=1);

namespace App\Analysing\DomainInterface\Analytics;

interface ExperimentInterface
{
    /**
     * @param array<string,mixed> $param
     *
     * @return array{
     *   experiment_id:string,
     *   user_id:string,
     *   variant:string,
     *   bucket:int,
     *   reason:string,
     *   rollout:int
     * }
     */
    public function assign(array $param): array;

    /**
     * @param array<string,mixed> $param
     *
     * @return array{accepted:int,experiment_id:string,variant:string}
     */
    public function expose(array $param): array;
}
