# Analysing entity-first query retirement

Current slice pass for Analysing.

## Retired schema/query-first sources

The following SQL template files are no longer the source of analytics read-model behavior:

- `src/queries/funnel.sql`
- `src/queries/retention.sql`
- `src/queries/cohort.sql`
- `src/queries/anomaly/series.sql`
- `src/queries/metric_tree/activation.sql`
- `src/queries/metric_tree/north_star_root.sql`
- `src/queries/metric_tree/revenue.sql`

These files were placeholder SQL templates and not Doctrine-owned entity-first model definitions.

## Entity-first replacement

Analytics read paths now go through `AnalyticsRepositoryInterface` / `AnalyticsRepository` and existing Doctrine entities:

- `AnalyticsFunnelDailyEntity`
- `AnalyticsRetentionCohortDailyEntity`
- `AnalyticsMetricSnapshotEntity`

New repository methods:

- `fetchCohort()`
- `fetchAnomalySeries()`
- `fetchLatestMetricValue()`

## Objecting decision

No Objecting embeddables were added in this pass. These analytics rows are aggregate/runtime read-model records, not long-lived business aggregates. Existing lifecycle fields remain local to the aggregate read model to avoid schema drift.

## Legacy donor check

`Entity-src(6).zip` was checked. There is no old `Analysing` monolith with missing Doctrine relations to transfer.
