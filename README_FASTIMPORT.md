# Fast-Import Package — Analytics Kernel (Updated naming)

This bundle reconstructs a standalone **Analytics Kernel** component history from the uploaded archives.
It includes an initial skeleton commit and then one commit per archive.

## Commits
0. chore(analytics-kernel-backend): skeleton repo bootstrap
1. feat(analytics-kernel-backend): core events & metrics
2. feat(analytics-kernel-backend): ingestion & ETL adapters — order funnel hooks
3. feat(analytics-kernel-backend): aggregations & dashboards, consent wiring
4. feat(analytics-kernel-backend): order-domain adapters (checkout/payment/refund/shipment)
5. feat(analytics-kernel-backend): stabilization, config polish, CI hooks

## Import instructions

```bash
git init analytics-kernel
cd analytics-kernel
git fast-import < fast-import_analytics-kernel-backend_001-005_with-skeleton.txt
git checkout master
```

> Author: Oleksandr Tishchenko <dev@smartresponsor.com>
> Dates: Human-like spread around Aug–Oct 2025 (-0500)
