<?php

declare(strict_types=1);

namespace App\DomainInterface\Analytics;

interface FlagInterface
{
    /**
     * @param array<string,mixed> $param
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
     * @param array<string,mixed> $param
     *
     * @return array{accepted:int,flag_key:string,enabled:bool}
     */
    public function expose(array $param): array;
}
