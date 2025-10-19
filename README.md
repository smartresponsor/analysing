# AnalyticsKernel — Iteration 4.3 (Report Exporters)

Экспорт аналитических отчётов CSV/XLSX (на текущем этапе XLSX — CSV-fallback; полноценный XLSX планируется через PhpSpreadsheet).

## Состав
- Entity: `ExportJob`
- Services: `ReportExporterService`, `ReportGeneratorService`
- CLI: `app:analytics:export <from> <to> [--vendor=] [--currency=] [--format=csv|xlsx]`
- Config: `config/packages/analytics_kernel_iter_4_3.yaml`
- Tests: `ExportersSmokeTest.php`

## Примеры
```bash
php bin/console app:analytics:export 2025-09-01 2025-09-30 --currency=USD --format=csv
php bin/console app:analytics:export 2025-09-01 2025-09-30 --vendor=501 --format=xlsx
```
