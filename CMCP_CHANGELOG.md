# CMCP execution journal

## 2026-09-13 — repository implementation baseline

### Reconnaissance

- Read target `AGENTS.md`, `README.md`, `composer.json`, service configuration, current entity-first retirement note, and relationship/lifecycle hardening note.
- Inspected the current Git state before writes: `master` with a large pre-existing migration worktree (163 status entries). Existing work is preserved and treated as the baseline, not reverted.
- Read mandatory neighboring contracts from Objecting, Cruding, Viewing, and Interfacing (`AGENTS.md`, `README.md`, `composer.json`, plus Objecting platform constraints).
- Read Canonization owner material and normative rules from `.canonization/Governance/Architecture/`, including `Canon019NoAlternativeLayerTaxonomyRule`, `Canon022StandaloneApplicationDependencyBaselineRule`, `Canon026PlatformVersionBaselineRule`, and the canonical rules journal.
- Read Gating `AGENTS.md`, `README.md`, and `composer.json` as the executable enforcement companion.
- Confirmed `config/bundles.php` is absent, so Canon022 standalone detection does not apply automatically to this repository; dependency changes must follow the explicit Analysing integration contract rather than guessing standalone status.

### Canon mapping

- Canon019 -> target has removed the old root `src/Domain` / `src/DomainInterface` topology; `composer lint:canon` and `composer guard:domain-hygiene` currently report zero Domain candidates.
- Canon022 -> standalone baseline gate is not automatically applicable because the target lacks the required standalone boot surface. The task-specific Objecting/Cruding/Viewing/Interfacing dependency contour remains a separate explicit integration requirement to verify.
- Canon026 -> target already requires PHP `^8.4` and Symfony `^8.1`; no downgrade compatibility work is allowed.
- Objecting -> analytics projection/runtime records are not blindly promoted to business aggregates; lifecycle/system fields require semantic classification before adopting field packs.
- Cruding -> Analysing must not duplicate generic CRUD controllers/routes; analytics business endpoints remain component-owned.
- Viewing/Interfacing -> neutral presentation payload/rendering boundaries belong to those components; Analysing should not absorb shell/template ownership.

### Market / maturity baseline

- RC expectations: typed ingestion/query boundaries, funnels/retention/cohorts, experiments, alerts/anomaly handling, deterministic exports, observability, privacy/security controls, and reproducible tests/gates.
- Growth track after RC: deeper self-service analytics, richer experimentation/statistical methods, qualitative/session context integration through owning components, and broader product analytics workflows.
- RC is not blocked on speculative session replay, UI expansion, or unrelated streaming infrastructure.

### Selected RC-critical workstream

- Finish the current Symfony-oriented migration and remove executable correctness debt without undoing the existing entity-first work.
- Close PHPStan/test/gate failures, packaging constraint debt, DI/contract mismatches, and release-readiness tails that are factually in scope.
- Preserve component responsibility boundaries and avoid speculative cross-component implementation.

### Material risks

- Large pre-existing dirty worktree means every patch must be narrow and must not overwrite unrelated migration work.
- `composer validate --strict` initially failed because `administering/administration` and `objecting/object` used unbound `*@dev` constraints; this packaging debt was closed during implementation.

## 2026-09-13/14 — RC implementation

### Material implementation

- Replaced the component-local Tenant identity model with canonical Vendor identity: `VendorId`, `VendorScope`, Vendor HTTP context/resolver/subscribers, `vendor_id`, and `X-SR-VENDOR` now carry analytics partition identity.
- Completed the explicit application dependency contour in Composer: Objecting, Cruding, Viewing, and Interfacing are direct dependencies; local Collectioning/Tabling repositories are wired for Cruding resolution.
- Added `minimum-stability: dev` with `prefer-stable: true`, replaced unbound dev constraints with explicit branch constraints, and refreshed `composer.lock` through a successful scoped Composer solve.
- Materialized current Gating severity/config inputs and an Analysing component profile. Standalone-only rules remain non-applicable because this package does not expose the standalone Symfony boot pair required by Canon022.
- Applied technical-role-first canonicalization across the PHP tree: component declarations are `Analytics*` subject-prefixed, DTOs use the `DTO` root/suffix, and Factory/Builder/Provider/Resolver/EventSubscriber/Policy responsibilities moved to their owning role roots.
- Removed the now-empty forbidden `src/Domain` and `src/Infrastructure` roots with a fail-closed cleanup tool.
- Renamed subject-owned component YAML files to `analytics_*` names and updated runtime/config/test references.
- Renamed the Symfony DI extension to `App\\Analysing\\DependencyInjection\\AnalyticsExtension` and updated bundle metadata/tests.
- Added persistent branch-coverage tooling through `composer test:coverage`.
- Reconciled service wiring and test contracts after the structural move; PHP DI now loads the newly canonical implementation roots.

### Packaging and boundary evidence

- `composer validate --strict --check-lock` passed after dependency/constraint normalization.
- Composer resolution installed the required direct dependency contour and reported no security vulnerability advisories.
- Generic CRUD ownership remains in Cruding; no component-local generic CRUD route/controller surface was introduced.
- Historical `report/` evidence intentionally retains retired architecture names. The Analysing Gating profile therefore does not treat history-wide token matches as current runtime architecture; live topology is enforced by structural/PSR-4/identity Canon rules.

### Verification status

- `composer analyse`: PHPStan level max, 185 source files, 0 errors after the final reference reconciliation.
- `composer test`: 171 tests, 558 assertions, green; 4 existing deprecations remain non-blocking evidence.
- Changed PHP syntax lint: green for the inspected changed/untracked PHP set.
- Final Composer validation: `composer validate --strict --check-lock` green.
- Final PHPStan: `composer analyse` green at level max across 185 source files.
- Final PHPUnit: `composer test` green with 171 tests / 558 assertions; 4 deprecations remain non-blocking.
- Final coverage execution: PHPUnit 11.5.55 + Xdebug 3.5.1 green with persistent text evidence; lines 52.1%, methods 33.6%, branches 58.4% (HIGH_TEST_DEBT warning, non-blocking).
- Final Gating: 32 rules, 0 failed, 3 warnings, 4 skipped. Warnings are the intentional legacy namespaced metrics tool, semantic PHPDoc coverage debt, and test coverage debt.
- Local guards green: layer scan, Domain hygiene, InterfaceInterface, PHP header; namespace report has 185 App\\Analysing files, banned=0, unknownRoot=0, with only the root bundle bootstrap reported as nonCanonical by the legacy report-only guard.
- The transitional canonical reference reconciler was retired and removed from the supported Composer surface after repeat execution proved unsafe for escaped FQCN strings. The repository was recovered with a narrow token repair and re-verified green afterward.
- Git audit: `master` is protected and already one commit ahead of `origin/master`; direct protected-branch push is therefore forbidden. Because the branch-switch guard rejects a dirty worktree, the verified snapshot is committed locally first, then a dedicated RC feature branch is created at that commit and published.
- Gating portability was hardened before integration: `gating/gate` is now a real `require-dev` QA dependency on the verified `dev-cruding-profile-scope-fix-v2` branch, wired by local path repository for development. `composer gate` executes `vendor/bin/gating` with the component-owned Analysing profile; the generated local `.gating/vendor` pack is no longer part of the publishable contract.
- Composer autoload after the Gating install exposed and closed one hidden test-only PSR-4 namespace defect in `tests/Unit/Command/AnalyticsExportCommandTest.php`.
- One-off CMCP migration mutation commands were removed from the final Composer surface; their local scripts are retained only as ignored execution evidence. Stable QA remains `analyse`, `test`, `test:coverage`, `gate`, and the non-mutating guards.
- Final standalone/browser verification exposed and fixed a real DI ordering defect: the broad `App\\Analysing\\` YAML resource had overridden controller services back to private. Controller-specific public/tagged resources now load after the broad resource in both service YAMLs.
- Playwright configuration was consolidated onto the pre-existing `playwright.config.ts`; its stale `tests/Playwright` root now points at the actual `tests` directory and owns the standalone `/status` web-server smoke. `npm test` passes 1/1 Chromium test.
- Added reproducible `test:behavioral-coverage` evidence production. Canon042 now passes with explicit inventories: functional 1/1, behavioral 1/1, UI 0/0 (no eligible UI surface), critical 1/1.
- Post-runtime-fix verification: PHPStan level max is green across 186 source files; PHPUnit is green at 171 tests / 559 assertions with 4 deprecations; Gating reports 61 rules, 0 failed, 3 warnings, 9 skipped. Remaining warnings are legacy tooling architecture review, PHPDoc debt, and PHP line/method/branch coverage debt.
- Final migration portability hardening: the initial Doctrine migration now renders from a frozen DBAL `Schema` instead of SQLite-specific literal SQL. A permanent PHPUnit guard renders the schema through `PostgreSQLPlatform` and rejects SQLite-only `AUTOINCREMENT`/`CLOB` tokens.
- Final persistence verification: the disposable test SQLite database was removed, the initial migration replayed from an empty database, and `schema:parity` passed with mapping and database schema synchronized and migrations up to date.
- Final behavioral verification exposed a PHP built-in-server routing artifact: direct `php -S ... public/index.php` returned 404 although Symfony `debug:router` contained `/status`. A repository-owned `tools/qa/playwright-router.php` now normalizes front-controller server variables for the test server only; production `public/index.php` remains unchanged.
- Playwright now uses an isolated loopback port and the QA router. Fresh `npm test` passes 1/1 Chromium `/status` smoke, and Canon042 remains green at functional 1/1, behavioral 1/1, UI 0/0, critical 1/1.
- Final verification after all repairs: Composer/PHPStan/schema gates green; PHPUnit 172 tests / 564 assertions green with 4 non-blocking deprecations; fresh coverage evidence regenerated; Gating 61 rules, 0 failed, 3 warnings, 9 skipped.
- Final remaining warnings are explicitly non-blocking RC debt: the legacy namespaced tooling type, semantic PHPDoc coverage, and PHP test coverage thresholds. No authorized in-scope hard failure remains.

## 2026-10-04 — autonomous RC reconnaissance and remediation baseline

### Reconnaissance

- Read the current Analysing `AGENTS.md`, `README.md`, Composer manifests, bundle/Doctrine configuration, CMCP journal, repository-facing Markdown/AsciiDoc relevant to the current RED contour, and the supplied CanonScanning/Inspecting evidence.
- Inspected the current Git state before new writes: branch `rc/analysing-canon-vendor-20260913`, existing dirty paths preserved and classified before reuse; no reset, clean, stash, or destructive reconciliation is permitted.
- Read the mandatory Objecting, Cruding, Viewing, and Interfacing contracts available in their repositories; Interfacing has no `MANIFEST.json`, which was confirmed by Console MCP rather than inferred.
- Read Gating owner contracts and the Failing Composer package identity; Failing is `failing/failure` and exposes the `App\\Failing\\` Symfony bundle namespace.
- Consumed the supplied Inspecting report for fingerprint `e843024f8976da74197b585d3c5743262b56b46ef9a24aaa622eecbf443e80a1`: 33 medium observational maintainability/design findings and no autofixable findings. It is baseline evidence, not an automatic RC blocker.

### Canon mapping consulted

- Canon022 `Canon022StandaloneApplicationDependencyBaselineRule.md` -> Analysing has `bin/console` plus `config/bundles.php`, so the standalone baseline applies. Current RED evidence requires direct `failing/failure` in development and production manifests and `App\\Failing\\FailingBundle` registration.
- Canon045 `Canon045DevelopmentComposerRepositoryClosureRule.md` -> the root development manifest must expose the reachable local `../Failing` repository required through Viewing.
- Canon047 `Canon047RepositoryOwnsDoctrineManagerRule.md` -> direct `EntityManagerInterface` access outside `src/Repository/` must be replaced by repository contracts.
- Canon052 `Canon052GatingIntegrationRule.md` -> `gating/gate` remains a development dependency, `composer gate` is the standard entry point, `quality` includes `@gate`, and consumer `.gating/` is artifact-only; a non-executable README is permitted.
- Canon054 `Canon054DoctrinePhysicalIdentifierNamingRule.md` -> standalone ORM configuration must use `doctrine.orm.naming_strategy.underscore_number_aware`.
- Canon055 `Canon055PlatformIdentityTerminologyRule.md` -> current human-facing shared-platform prose must use neutral platform vocabulary; historical/consumer identity references must be explicitly framed as such.
- Canon056/057/058/059/060/061/062/063 -> the two external analytics API GET operations need a canonical YAML OpenAPI source under `config/openapi/`, method/path parity, canonical version placement if versioned, and a direct Nelmio runtime dependency once Analysing owns OpenAPI.

### Selected RC-critical workstream

- Close the supplied hard Canon RED backlog without expanding Analysing beyond analytics query/metric/report responsibility: dependency closure, persistence ownership, Gating integration, Doctrine naming strategy, neutral platform terminology, and API/OpenAPI parity.
- Preserve generic CRUD ownership in Cruding, presentation ownership in Viewing/Interfacing, and Objecting system-field ownership; do not introduce `src/Domain`, Ports/Adapters, or alternative namespaces.

### Growth workstream (post-RC, non-blocking)

- Product-analytics maturity may later add richer self-service exploration, experimentation/statistics, diagnostics, and DX around funnels/retention/path analysis, while session replay UI, stream-processing infrastructure, storage-engine ownership, and shell presentation remain outside Analysing.

### Material risks and gates

- Existing dirty changes include partial canon remediation and must not be overwritten blindly; the duplicate development Gating repository entry is specifically non-canonical duplication to reconcile.
- Required deterministic acceptance includes current Gating, Composer validation/lock parity, PHP syntax/static analysis, PHPUnit, Symfony container/YAML, Doctrine schema parity, and a post-mutation Inspecting run because the supplied Inspecting fingerprint becomes stale after remediation.
- The first current `composer gate` start attempt was capacity-admitted as light-only by Console MCP and did not start a process; this is a transient orchestration capacity condition, not a repository blocker.

### Current execution verification checkpoint

- `composer validate --strict --check-lock`: GREEN.
- `composer analyse`: GREEN at PHPStan max over 194 source files.
- `composer test`: GREEN, 172 tests / 566 assertions; 4 existing deprecations remain non-blocking.
- `composer gate`: GREEN for the currently selected automatic Gating surface (10 rules, 0 failed, 0 warnings, 3 skipped); because no component profile was selected by this invocation, this is supporting evidence rather than a substitute for the supplied CanonScanning contour.
- `composer standalone:smoke`: GREEN on Symfony 8.1.8 / PHP 8.4.13 in `test`.
- `composer lint:canon`: GREEN for structural hard criteria: 194 files, zero duplicate basenames, zero Domain candidates, zero InterfaceInterface anomalies. The scanner reports 68 missing mirrors as inventory, not a command failure.
- `composer guard:namespace`: GREEN/report-only with 194 `App\\Analysing` PHP files, zero banned roots and zero unknown roots; only the root `AnalysingBundle` and `Kernel` bootstrap declarations are reported as non-canonical by the legacy report heuristic.
- Existing managed PHP runtime on `127.0.0.1:8099` was probed before browser verification and was not running. `npm test` then started the repository QA server and passed the Chromium `/status` behavioral smoke 1/1.
- `playwright.config.ts` now sets `reuseExistingServer: true`, so subsequent browser verification obeys the engine `REUSE_EXISTING_FIRST` contract instead of forcing a duplicate server when a healthy runtime already exists.
- `composer schema:parity`: GREEN using a disposable in-memory SQLite database; both repository migrations replay cleanly and converge exactly to current Doctrine metadata (9 tables / 2 migrations). The existing working test database is not mutated to obtain acceptance evidence.
- Changed runtime/browser verification remains GREEN: the managed `127.0.0.1:8099` runtime was probed first and was absent, then `npm test` started the repository QA server and passed the Chromium `/status` behavioral smoke 1/1 with `reuseExistingServer: true` preserved for healthy-runtime reuse.
- Fresh post-mutation Inspecting completed successfully at `D:\\PhpstormProjects\\www\\Inspecting\\.inspecting\\reports\\D--PhpstormProjects-www-Analysing-20261004-082629.json`: PHPStan errors 0, 33 medium observational maintainability/design findings, 0 autofixable findings. These remain non-blocking technical-debt evidence rather than a hard Canon failure.
- Current-window deterministic re-verification is GREEN: `composer validate --strict --check-lock`, `composer analyse`, `composer test` (172 tests / 566 assertions, 4 non-blocking deprecations), `composer schema:parity`, and the standard `composer gate` surface (10 rules, 0 failed, 0 warnings, 3 profile-dependent skips).

### 2026-10-04 — material verification continuation

- Re-ran the current repository gates through Console MCP. `composer validate --strict --check-lock`, changed/untracked PHP syntax lint, Symfony `lint:container`, Symfony `lint:yaml config`, standalone Symfony smoke, and the explicit API router inventory were GREEN.
- Applied the guarded pending test-environment migration only after a Doctrine migrations dry-run plan fingerprint proved the change was the two canonical snake_case column renames. The resulting Doctrine schema/migration parity was GREEN.
- Strengthened the persistent `composer analyse` gate so it follows `phpstan.neon` and covers both `src` and `tests`; the resulting PHPStan max run covered 295 files with zero errors.
- A fresh Inspecting pass initially found one high `class.notFound` in `MigrationPlatformCompatibilityTest`; the test was changed to reflect the explicitly loaded migration by string class name rather than requiring Composer to resolve a non-autoloaded symbol. PHPUnit remained GREEN at 172 tests / 566 assertions.
- Fresh post-fix Inspecting report `D:\\PhpstormProjects\\www\\Inspecting\\.inspecting\\reports\\D--PhpstormProjects-www-Analysing-20261004-081917.json` completed with zero PHPStan errors/file errors and 33 medium observational structural findings, matching the previous non-blocking maintainability/design contour; no high finding and no autofixable finding remains in that report.
- Before final commit, the live workspace began receiving concurrent writes from other active Analysing engine tasks. The observed drift alternated the Composer migration autoload mapping and its manifest-test expectation, causing `composer quality` to fail on opposite sides of the same two-file contract across consecutive runs. This is an active concurrent-writer integration blocker, not a stable repository failure.
- No Git commit, push, reset, stash, clean, or overwrite was performed after the concurrent-writer condition was confirmed. The current task must re-establish a stable live snapshot and re-run aggregate acceptance before publication.
- A later stable acceptance snapshot passed aggregate `composer quality`: PHPStan max over 295 files, PHPUnit 172 tests / 566 assertions with 4 existing deprecations, isolated migration/schema parity across 9 tables / 2 migrations, and Gating with 0 failures / 0 warnings on its current automatic surface.
- Fresh Inspecting report `D:\\PhpstormProjects\\www\\Inspecting\\.inspecting\\reports\\D--PhpstormProjects-www-Analysing-20261004-082312.json` completed with zero PHPStan errors/file errors, zero high findings, 33 medium observational findings, and zero autofixable findings.
- Repository-local Playwright verification passed the Chromium `/status` smoke 1/1 after probing the managed runtime first; no user-observable visual UI surface is eligible for screenshot evidence in this canon/persistence/API remediation.
- Git integration remains intentionally unperformed while the worktree contains mixed pre-existing/concurrent modifications whose path-level ownership cannot be separated safely from the in-scope Composer/config remediation without commingling protected work.
