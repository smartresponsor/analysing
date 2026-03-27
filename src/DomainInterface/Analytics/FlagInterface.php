<?php

declare(strict_types=1);

namespace App\DomainInterface\Analytics;

interface FlagInterface
{
    /**
     * @param array{
     *   flag_key:mixed,
     *   user_id:mixed,
     *   rollout?:mixed,
     *   allow?:mixed
     * } $param
     *
     * @return array{
     *   flag_key:string,
     *   user_id:string,
     *   enabled:bool,
     *   bucket:int,
     *   reason:string,
     *   rollout:int
     * }
     */
    public function evaluate(array $param): array;

    /**
     * @param array{
     *   flag_key:mixed,
     *   tenant_id:mixed,
     *   user_id:mixed,
     *   enabled:mixed
     * } $param
     *
     * @return array{accepted:int,flag_key:string,enabled:bool}
     */
    public function expose(array $param): array;
}
