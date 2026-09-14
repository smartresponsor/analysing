<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface;

use App\Analysing\Entity\Analytics\AnalyticsExportJobEntity;

interface AnalyticsExportJobViewInterface
{
    /**
     * @return array{id:int|null,type:string,status:string,attempts:int,error:?string,created_at:string,finished_at:?string,payload:array<string,mixed>,download_url:?string}
     */
    public static function toArray(AnalyticsExportJobEntity $job): array;
}
