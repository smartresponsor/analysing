# E2E historical lineage integration

The current Symfony and PHPUnit configuration remains authoritative.
The historical branch is retained as a merge parent, not as an executable legacy tree.
Canonical cohort and export fixtures, repository-based API contracts, and
Symfony HTTP tests with repository mocks were ported in separate commits.
Playwright cohort checks target public status, not authenticated user journeys
or export metrics. They complement the existing dashboard renderer smoke test.
The historical Panther scenario, bootstrap, unused HTTP helper and legacy
production fixture are retained in the parent history, not activated.
Historical fixture directory spelling differs only by case from the canonical
Factories directory; do not check out both on Windows.
Real database-backed browser export tests and Panther execution remain follow-up.
