# Factories

This directory will contain test data factories used across:

- Symfony application tests
- Panther tests
- Playwright E2E
- Synthetic / canary flows

## Planned Factories

- UserFactory
- ExportJobFactory
- AnalyticsDatasetFactory

## Goals

- Provide reusable data builders
- Support Faker-based variability
- Keep canonical deterministic seeds for baseline scenarios
- Enable cohort-specific data generation

## Usage

Factories should:
- create realistic data
- avoid breaking domain constraints
- allow overriding fields
- support bulk generation

Example (conceptual):

UserFactory::existingUser()
UserFactory::newUser()
UserFactory::virtualUser()

ExportJobFactory::completed()
ExportJobFactory::failed()
ExportJobFactory::pending()
