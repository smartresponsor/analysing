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
