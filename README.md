# AnalyticsKernel — Iteration 4.1 (Foundation)

Базовый модуль аналитики KPI и агрегатов SmartResponsor.

## Состав
- Entity: `MetricSnapshot`
- Service: `AnalyticsCollector`
- CLI: `app:analytics:refresh`
- Config: `config/packages/analytics_kernel_iter_4_1.yaml`
- Tests: `AnalyticsSmokeTest.php`

## Пример
```bash
php bin/console app:analytics:refresh
# → Refreshed 42 metric snapshots
```
