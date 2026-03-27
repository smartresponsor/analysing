<?php

declare(strict_types=1);

namespace App\DomainInterface\Analytics;

interface ExperimentInterface
{
    /**
     * @param array{
     *   experiment_id:mixed,
     *   user_id:mixed,
     *   rollout?:mixed,
     *   variants?:mixed
     * } $param
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
     * @param array{
     *   experiment_id:mixed,
     *   tenant_id:mixed,
     *   user_id:mixed,
     *   variant:mixed
     * } $param
     *
     * @return array{accepted:int,experiment_id:string,variant:string}
     */
    public function expose(array $param): array;
}
