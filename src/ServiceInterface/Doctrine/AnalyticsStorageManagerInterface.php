<?php

declare(strict_types=1);

namespace App\Analysing\ServiceInterface\Doctrine;

interface AnalyticsStorageManagerInterface
{
    /** @return array{ready:bool,mode:string,path:?string,required_tables:list<string>,existing_tables:list<string>,missing_tables:list<string>} */
    public function inspect(): array;

    /** @return array{mode:string,path:?string,executed_sql_count:int,seeded:bool,created_tables:list<string>,missing_tables:list<string>} */
    public function prepare(bool $seed = false): array;
}
