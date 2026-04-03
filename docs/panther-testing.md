# Panther Testing Strategy

## Execution Modes

This project supports two Panther execution modes:

### A. Headless mode

Recommended for:
- CI pipelines
- fast local smoke checks
- deterministic automated runs

### B. Visible browser mode

Recommended for:
- local debugging
- screenshot-driven issue triage
- interactive investigation of end-to-end failures

---

## Recommended Usage

### Headless

Use headless mode by default in CI and in most developer workflows.

### Visible

Switch to visible mode when reproducing a failure or validating browser behavior manually.

---

## Planned Panther Test Suite

Directory layout:

- `tests/Panther/Analytics/ExportFlowPantherTest.php`
- `tests/Panther/Analytics/ExistingUserJourneyPantherTest.php`
- `tests/Panther/Analytics/NewUserJourneyPantherTest.php`

---

## Cohort Usage

### Existing users
- regression paths
- previously generated analytics data

### New users
- onboarding flows
- empty-state validation

### Virtual users
- not primary Panther target
- mostly reserved for Playwright synthetic and canary checks

---

## Execution Principle

Panther sits between Symfony application tests and Playwright:

1. Symfony application tests validate contracts and backend behavior
2. Panther validates browser-backed Symfony flows
3. Playwright validates broader end-to-end and canary scenarios

---

## Notes

- Headless mode should be the default mode in automation
- Visible mode should remain available for local debugging
- Panther tests should focus on smoke and browser-sensitive flows
- Playwright will remain the main full E2E and synthetic user layer
