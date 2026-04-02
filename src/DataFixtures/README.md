# Data Fixtures Foundation

This directory defines the canonical fixture layout for analytics testing.

## Planned Fixtures

- `AnalyticsDemoFixtures.php`
- `ExistingUserFixtures.php`
- `NewUserFixtures.php`
- `CanarySyntheticFixtures.php`

## Principles

- Deterministic seeded data
- Clear separation between cohorts
- Safe re-seeding
- Stable identifiers for synthetic users
- Compatibility with Symfony application tests, Panther and Playwright

## Cohorts

### Existing Users
Historical users with seeded analytics history and previously completed export jobs.

### New Users
Fresh users with minimal or no analytics history to validate onboarding and empty-state flows.

### Virtual Users
Synthetic users intended for canary traffic and production-safe smoke flows.
