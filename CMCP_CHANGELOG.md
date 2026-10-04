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
- Final post-hook/post-commit Inspecting completed successfully at `D:\\PhpstormProjects\\www\\Inspecting\\.inspecting\\reports\\D--PhpstormProjects-www-Analysing-20261004-083424.json`: PHPStan errors 0, 33 medium observational maintainability/design findings, 0 autofixable findings. These remain non-blocking technical-debt evidence rather than a hard Canon failure.
- Final post-hook deterministic verification is GREEN: `composer validate --strict --check-lock`; aggregate `composer quality` (PHPStan max, PHPUnit 172 tests / 566 assertions with 4 non-blocking deprecations, disposable schema parity 9 tables / 2 migrations, Gating 10 rules / 0 failed / 0 warnings / 3 profile-dependent skips); and Playwright Chromium `/status` behavioral smoke 1/1.
- Git integration created signed commits `93522dd` (`Remediate Analysing RC canon failures`) and `97a6619` (`Document Analysing RC canon baseline`). The branch was clean, ahead 2 / behind 0, then published successfully to `origin/rc/analysing-canon-vendor-20260913`.

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
- Git integration was initially deferred while the worktree contained mixed pre-existing/concurrent modifications whose path-level ownership could not be separated safely from the in-scope Composer/config remediation without commingling protected work.

### 2026-10-04 — final stable RC integration checkpoint

- Concurrent activity converged into two coherent local commits: `93522dd` (`Remediate Analysing RC canon failures`) and `97a6619` (`Document Analysing RC canon baseline`). The worktree was clean after those commits, and no destructive reconciliation was used.
- After `git fetch origin --prune`, the RC branch remained clean, 2 commits ahead and 0 behind its configured upstream; `origin/master` advanced independently and does not create divergence on this RC branch.
- Final committed-HEAD acceptance is GREEN: `composer quality` passed PHPStan max over 295 files, PHPUnit 172 tests / 566 assertions (4 existing deprecations), isolated migration/schema parity across 9 tables / 2 migrations, and Gating with 0 failures / 0 warnings on its automatic surface.
- Symfony `lint:container --env=test`, `lint:yaml config --env=test`, standalone Symfony smoke, and Chromium Playwright `/status` smoke are GREEN. The existing managed runtime was probed before browser verification and was reused when healthy.
- Fresh post-mutation Inspecting report `D:\\PhpstormProjects\\www\\Inspecting\\.inspecting\\reports\\D--PhpstormProjects-www-Analysing-20261004-083007.json` has zero PHPStan errors/file errors, zero high findings, 33 medium observational findings, and zero autofixable findings. These remain non-blocking maintainability/design debt.
- RC validation reports `rc_diagnostic_green`; its only two Canon warnings are TODO/STUB markers in historical/planning documentation and are non-blocking. No hard Canon remediation item from the supplied RED envelope remains open.
- No user-observable UI was changed by this remediation, so screenshot evidence is not applicable; the central Visual Gallery remains the canonical inspection surface.

### 2026-10-04 — task engine-20261004084217-analysing-e1049b acceptance checkpoint

- Re-established the live repository baseline on `rc/analysing-canon-vendor-20260913` at HEAD `52188b27e00715c2a82415a03f38eed122f8a2e6`; the worktree was clean and the branch matched its upstream before this journal-only write.
- Re-read the supplied 2026-09-29 CanonScanning RED report and Inspecting evidence, the current Analysing runtime/configuration surfaces, the normative Canonization rules for Canon022/045/047/052/054/055/056-063, and the mandatory Objecting, Cruding, Viewing, Interfacing, and Gating contracts. Interfacing currently has no `MANIFEST.json`; this was confirmed by Console MCP.
- Confirmed the historical hard RED causes are already remediated in the current tree: Failing baseline/registration and Composer closure, repository-owned Doctrine manager access, artifact-only `.gating/`, underscore-number-aware Doctrine naming, neutral platform terminology, canonical `config/openapi/analytics_openapi.yaml`, direct Nelmio dependency, and runtime/OpenAPI GET path+method parity.
- Fresh deterministic acceptance is GREEN: `composer validate --strict --check-lock`; `composer gate` (10 rules, 0 failed, 0 warnings, 3 profile-dependent skips); `composer analyse` (PHPStan max, 295/295 files, 0 errors); `composer test` (172 tests, 566 assertions, 4 existing deprecations); `composer schema:parity` (9 tables, 2 migrations); and `composer standalone:smoke` (Symfony 8.1.8 / PHP 8.4.13).
- Runtime/browser policy was respected: the existing managed PHP process on `127.0.0.1:8099` was probed before browser verification and was not restarted. After a transient probe timeout it returned HTTP 200, and repository Playwright with `reuseExistingServer: true` passed the Chromium `/status` smoke 1/1.
- Aggregate `composer quality` could not be admitted as a single heavy worker because shared Console MCP capacity was in `RESOURCE_PRESSURE_WATCH / ENGINE_BACKLOG_HIGH`; its constituent deterministic gates were therefore executed directly and all passed. This is orchestration-capacity evidence, not a repository failure.
- No user-observable UI changed in this execution window, so fresh screenshot evidence is not applicable. The supplied Inspecting findings remain medium observational maintainability/design debt and do not reopen the remediated hard Canon backlog.

### 2026-10-04 — task engine-20261004085615-analysing-0dda6c maintainability remediation

- Reconfirmed a clean, synchronized baseline on `rc/analysing-canon-vendor-20260913` at HEAD `5058c27586faaf5784a67bd0bfdbe8943e71743e` before mutation.
- Re-consumed the supplied CanonScanning RED and Inspecting evidence and the normative Canonization rules for Canon022/045/047/052/054/055/056-063. The historical hard Canon failures remain remediated in the current tree.
- Selected the remaining Inspecting hotspot `AnalyticsExportCommand::execute()` as bounded RC-critical technical debt. Extracted scalar path parsing, current-month request construction, export-row assembly, target-directory preparation, and duration calculation into cohesive private methods without changing the command name, arguments, output contract, or exporter/dashboard boundaries.
- The first post-mutation PHPStan run correctly rejected an overly narrow extracted row PHPDoc because the KPI request date fields are nullable; the contract was widened to the factual `int|float|string|null` row-value union and PHPStan was rerun.
- Verification after repair is GREEN: PHP syntax lint for the changed command; PHPStan max over 295 files; PHPUnit 172 tests / 566 assertions with 4 existing deprecations; Gating 10 rules / 0 failed / 0 warnings / 3 profile-dependent skips.
- Fresh post-mutation Inspecting report `D:\\PhpstormProjects\\www\\Inspecting\\.inspecting\\reports\\D--PhpstormProjects-www-Analysing-20261004-090141.json` has zero PHPStan errors and reduces the structural finding count from the supplied 33 to 31. Both former `AnalyticsExportCommand::execute()` findings (long method and cyclomatic complexity 18) are absent. Remaining 31 findings are medium observational debt with zero autofixable findings.
- No user-observable UI, navigation, form, or browser flow changed; screenshot evidence is not applicable for this command-only refactor.

### 2026-10-04 — task engine-20261004092318-analysing-8dbd5a command maintainability checkpoint

- Re-established the live baseline on `rc/analysing-canon-vendor-20260913` and preserved the pre-existing `AnalyticsAlertsRunCommand.php` refactor as coherent in-scope work: its result-processing loop is extracted into private helpers without changing the command name, evaluator/dispatcher contracts, log semantics, console summary, or success/failure policy.
- Re-read the supplied CanonScanning RED and Inspecting evidence, current repository Markdown/configuration/source/test surfaces, and the normative Canonization rules for Canon022/045/047/052/054/055/056-063. The historical hard Canon failures remain remediated in the current tree.
- Re-read the mandatory Objecting, Cruding, Viewing, Interfacing, and Gating owner contracts. Interfacing still has no `MANIFEST.json`; this was confirmed by Console MCP rather than inferred.
- RC-critical scope remains correctness, deterministic analytics/report/export/alert behavior, API contract parity, persistence ownership, diagnostics, and executable verification. Growth work such as richer exploration/experimentation UX remains post-RC; session replay ownership, streaming infrastructure, storage-engine ownership, and shared shell rendering remain outside Analysing.
- Added focused `AlertsRunCommandTest` regression coverage for malformed evaluator rows, unmatched rows, invalid matched payloads, dispatcher exceptions, exit status, and emitted matched/dispatched/failed/skipped counters.
- During this execution window, `src/Service/Alerts/AnalyticsAlertEvaluator.php` became dirty from a concurrent writer after the initial baseline. Its separate maintainability refactor was preserved and verified with the live tree but is not claimed or automatically included in this task's Git ownership.
- Deterministic verification on the live tree is GREEN: `composer validate --strict --check-lock`; changed PHP syntax lint; `composer analyse` (PHPStan max, 295/295 files, zero errors); `composer test` (173 tests / 572 assertions, 4 existing deprecations); `composer gate` (10 rules, 0 failed, 0 warnings, 3 profile-dependent skips); `composer schema:parity` (9 tables / 2 migrations); Symfony `lint:container --env=test`; Symfony `lint:yaml config --parse-tags --env=test`; and `composer standalone:smoke` (Symfony 8.1.8 / PHP 8.4.13).
- Fresh post-mutation Inspecting report `D:\\PhpstormProjects\\www\\Inspecting\\.inspecting\\reports\\D--PhpstormProjects-www-Analysing-20261004-092924.json` has zero PHPStan errors/file errors, zero high findings, 28 medium observational findings, and zero autofixable findings. `AnalyticsAlertsRunCommand::execute()` is no longer reported as a long method. Because the concurrent evaluator refactor was also present during this scan, the full finding-count reduction is not attributed solely to this task.
- No browser/mobile UI, navigation, form, or user flow changed in this task-owned command/test scope; screenshot evidence is therefore not applicable. The central Visual Gallery remains the canonical artifact inspection surface.

### 2026-10-04 — task engine-20261004091608-analysing-4444de maintainability continuation

- Re-established the current Analysing baseline through Console MCP and re-consumed the supplied CanonScanning RED/Inspecting evidence before mutation. The historical hard Canon022/045/047/052/054/055/056-063 backlog remains remediated in the current tree; the current automatic `composer gate` surface is GREEN with 0 failures and 0 warnings.
- Re-read the normative Canonization guard matrix and the applicable Canon022, Canon047, Canon052, Canon054, Canon055, Canon056, and Canon063 textual rules, plus the mandatory Objecting, Cruding, Viewing, Interfacing, and Gating repository contracts. The RC-critical work remained within Analysing analytics execution/diagnostic responsibility; growth items such as deeper self-service exploration and richer product-analytics UX remain non-blocking and outside this remediation.
- Refactored `AnalyticsAlertsRunCommand::execute()` into a thin orchestration method with focused result-processing and message-building helpers while preserving malformed evaluator-result handling, dispatch-failure logging, output counters, and exit-code behavior.
- Refactored `AnalyticsAlertEvaluator::evaluate()` into orchestration plus bounded rule-loading, per-rule evaluation, and numeric comparison helpers while preserving condition validation, snapshot lookup behavior, logs, exceptions, result shape, and repository boundaries.
- A concurrent writer added `AlertsRunCommandTest::testExecuteSkipsMalformedResultsAndReportsDispatchFailure()` during this execution window. The change is semantically in-scope regression coverage for the same command refactor and is preserved rather than overwritten; subsequent PHPUnit execution passed it.
- Deterministic verification after the second remediation pass is GREEN: PHP syntax for both changed source files; PHPStan max over 295 files; PHPUnit 173 tests / 572 assertions with 4 existing deprecations; Gating 10 rules / 0 failed / 0 warnings / 3 profile-dependent skips.
- Fresh post-mutation Inspecting report `D:\\PhpstormProjects\\www\\Inspecting\\.inspecting\\reports\\D--PhpstormProjects-www-Analysing-20261004-092810.json` has zero PHPStan errors, zero autofixable findings, and 28 medium observational findings. This improves the previous task checkpoint from 31 findings to 28: `AnalyticsAlertsRunCommand::execute()` no longer reports long-method debt, and `AnalyticsAlertEvaluator::evaluate()` no longer reports either long-method or cyclomatic-complexity debt.
- Aggregate `composer quality` is GREEN after the final code/test snapshot: PHPStan max over 295 files, PHPUnit 173 tests / 572 assertions with 4 existing deprecations, isolated migration/schema parity across 9 tables / 2 migrations, and Gating 10 rules / 0 failed / 0 warnings / 3 profile-dependent skips.
- No user-observable UI, navigation, form, or browser flow changed in these command/service refactors, so fresh screenshot evidence is not applicable; the central Visual Gallery remains the inspection surface.
