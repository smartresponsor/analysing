# Testing Strategy

## Objectives

This project uses a layered testing stack to validate analytics flows with different speed and confidence levels.

### Layers

1. **Symfony application tests**
   - Fast API-level validation
   - Covers controllers, services, persistence boundaries and response contracts

2. **Symfony Panther tests**
   - Browser-backed validation close to the Symfony stack
   - Useful for smoke tests and real browser assertions

3. **Playwright tests**
   - Full end-to-end coverage
   - Best suited for critical user journeys and canary-grade synthetic checks

4. **Virtual users**
   - Small, stable synthetic scenarios
   - Intended for canary traffic, continuous smoke validation and production monitoring

---

## Cohorts

### Existing Users

These tests use seeded accounts with historical state and previously generated analytics data.

### New Users

These tests use newly provisioned accounts with minimal or empty analytics history.

### Virtual Users

These are synthetic identities used for canary checks and production-safe smoke validation.

---

## Fixture Principles

- Fixtures must be deterministic
- Synthetic users must be easy to identify
- Test data must be safe to re-seed repeatedly
- Existing-user data and new-user data must stay clearly separated
- Faker may be used for variability, but canonical fixture records must remain stable

---

## Recommended Fixture Layout

- `src/DataFixtures/AnalyticsDemoFixtures.php`
- `src/DataFixtures/ExistingUserFixtures.php`
- `src/DataFixtures/NewUserFixtures.php`
- `src/DataFixtures/CanarySyntheticFixtures.php`
- `tests/Fixtures/factories/UserFactory.php`
- `tests/Fixtures/factories/ExportJobFactory.php`
- `tests/Fixtures/factories/AnalyticsDatasetFactory.php`

---

## Execution Model

### Application tests

Use them for fast validation of:
- export job creation
- export job status
- metrics endpoint
- retry and failure scenarios

### Panther tests

Use them for:
- browser smoke coverage
- onboarding and existing-user flows
- screenshots or debugging on failure

### Playwright tests

Use them for:
- critical cohort-based journeys
- canary virtual user flows
- CI artifacts and external browser verification

---

## Observability Requirements

Every canary or synthetic flow should emit:
- cohort
- scenario
- job id when available
- duration
- success or failure outcome
- correlation id if supported by the application

---

## Definition of Done

A testing milestone is considered complete when:
- fixtures exist for all cohorts
- API tests cover critical flows
- browser tests cover smoke paths
- Playwright covers cohort journeys
- virtual users exist for canary traffic
- observability fields are available for test traffic
