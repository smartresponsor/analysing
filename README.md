# AnalyticsKernel — Iteration 4.5 (UI & Slack/Webhook hooks)

Минимальный HTML-дашборд (Twig) + Slack/Webhook-хуки для алертов.

## Что добавлено
- Controller: `DashboardPageController` → страница `/analytics` (добавь маршрут)
- Template: `templates/analytics/index.html.twig` (Chart.js CDN)
- Alerts: расширенный `NotificationDispatcher` с `webhook` и `slack_webhook`
- Config: `config/packages/analytics_kernel_iter_4_5.yaml`

## Маршрут (routes.yaml)
```yaml
analytics_page:
  path: /analytics
  controller: App\Controller\Analytics\DashboardPageController::index
```

## Пример использования Slack/Webhook
```php
$notify->send('High risk for vendor 501', ['slack_webhook' => 'https://hooks.slack.com/services/...']);
$notify->send('Finance alert', ['webhook' => 'https://example.com/webhook']);
```
