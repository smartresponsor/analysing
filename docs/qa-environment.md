# QA environment

Before running PHPUnit in this repository, verify the local PHP environment with:

```bash
php tools/qa/AnalyticsQaEnvironmentCheck.php
```

The check validates the PHPUnit-critical PHP extensions:

- dom
- json
- libxml
- mbstring
- tokenizer
- xml
- xmlwriter

It also reports optional extensions used by the local analytics runtime path:

- pdo
- pdo_sqlite

For Composer-based workflows, the canonical test entrypoint is:

```bash
composer test
```

That entrypoint now runs the QA preflight before PHPUnit.
