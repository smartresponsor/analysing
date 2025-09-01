# AnalyticsKernel — Iteration 4.4 (Alerts & Monitoring)

Алерты по KPI и комплаенсу, доставщик уведомлений.

## Состав
- Entities: `AlertRule`, `AlertLog`
- Services: `AlertEvaluator`, `NotificationDispatcher`
- CLI: `app:alerts:run`
- Config: `config/packages/analytics_kernel_iter_4_4.yaml`

## Типовые правила
- `negative_margin` (threshold = 0.0) — margin <= 0%
- `high_risk` (threshold = 80.0) — риск >= 80
- `limit_breach` (threshold = 1000000) — дневной оборот в minor-юнитах > порог

## Пример использования
1) Завести `AlertRule` записи в БД (миграцией или через админку).
2) Запустить:
```bash
php bin/console app:alerts:run
# Alerts evaluated. X alerts created.
```
