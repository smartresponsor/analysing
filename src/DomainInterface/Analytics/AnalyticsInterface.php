<?php

declare(strict_types=1);

namespace App\DomainInterface\Analytics;

interface AnalyticsInterface
{
    /**
     * @param array{
     *   tenant_id:mixed,
     *   app:mixed,
     *   env:mixed,
     *   from:mixed,
     *   to:mixed,
     *   steps:mixed
     * } $param
     *
     * @return list<array{day:mixed,user_count:mixed}>
     */
    public function runFunnel(array $param): array;

    /**
     * @param array{
     *   tenant_id:mixed,
     *   app:mixed,
     *   env:mixed,
     *   from:mixed,
     *   to:mixed,
     *   cohort:mixed,
     *   days:mixed
     * } $param
     *
     * @return list<array{day_offset:mixed,active_user:mixed}>
     */
    public function runRetention(array $param): array;

    /**
     * @param array{
     *   tenant_id:mixed,
     *   app:mixed,
     *   env:mixed,
     *   from:mixed,
     *   to:mixed,
     *   steps:mixed
     * } $param
     *
     * @return list<array{cohort_date:mixed,user_count:mixed}>
     */
    public function runCohort(array $param): array;
}
