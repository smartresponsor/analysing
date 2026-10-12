# Analysing RC quality and branch integration

This is a repository-specific, reproducible acceptance guide. The current
Symfony component is PHP 8.4+ and uses the `App\\Analysing\\` namespace.

## Required checks

From the Analysing repository root:

```shell
composer test
composer analyse
composer gate
composer schema:parity
npm test
```

`composer test` includes the Analytics QA preflight and PHPUnit. Static analysis
uses PHPStan at the repository-configured maximum level. The schema parity
check covers the mapped analytics tables and migrations. Playwright starts
the repository-owned test router defined in `playwright.config.ts` and
`tools/qa/playwright-router.php` and exercises HTTP/UI QA routes.

The cohort-labelled Playwright status canary verifies the public status
endpoint with synthetic request headers; it does **not** verify authorization
or cross-user isolation. Those require dedicated data-boundary tests.

## Canonical GREEN

An RC run must not be declared fully canonical solely because its working
tree and tests are green. Before the final decision:

1. Fetch remote refs and inspect all local and remote branches.
2. Identify commits absent from `master`; distinguish cherry-picked or
   rebased equivalent changes from genuinely missing behavior.
3. Evaluate valuable changes against current Symfony runtime and the
   component boundaries; do not merge obsolete files blindly.
4. Integrate retained changes through a reviewed, conflict-resolved path.
5. Run the checks above on the **final master HEAD** and confirm a clean,
   synchronized worktree.

GitHub Actions infrastructure failures are not automatically RC blockers.
Confirmed vulnerabilities, failing application tests, architectural
