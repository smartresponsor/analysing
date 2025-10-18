# AnalyticsKernel — Iteration 4.2 (Dashboard Engine)

JSON-дашборды KPI: агрегаты, таймсерии, топ вендоров.

## Состав
- DTO: `KpiRequest`
- Service: `DashboardService` (kpi, timeseries, byVendor)
- Controller: `DashboardController` (JSON API)
- Config: `config/packages/analytics_kernel_iter_4_2.yaml`
- Tests: `DashboardSmokeTest.php`

## Примеры маршрутов (routes.yaml)
```yaml
analytics_kpi:
  path: /api/analytics/kpi
  controller: App\Controller\Analytics\DashboardController::kpi

analytics_timeseries:
  path: /api/analytics/timeseries
  controller: App\Controller\Analytics\DashboardController::timeseries

analytics_top_vendors:
  path: /api/analytics/top-vendors
  controller: App\Controller\Analytics\DashboardController::topVendors
```
