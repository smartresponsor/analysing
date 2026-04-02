# E2E / Canary / Observability Milestone

## Goals

- End-to-end validation of analytics flows
- Introduce synthetic (virtual) users
- Canary traffic for safe production rollout
- Observability hooks for monitoring

---

## Architecture Overview

### 1. Cohorts

- Existing users (real traffic)
- New users (fresh flows)
- Virtual users (synthetic traffic)

### 2. Traffic Types

- Normal production traffic
- Canary traffic (small % routed to new logic)
- Synthetic traffic (Playwright / bots)

---

## Stack

- Symfony (API backend)
- Playwright (E2E testing)
- Panzer (load / orchestration)
- Kubernetes (deployment + scaling)

---

## Phases

### Phase 1 — E2E Baseline

- Setup Playwright
- Cover API endpoints:
  - export job creation
  - export job status
  - metrics endpoint

### Phase 2 — Synthetic Users

- Generate virtual users
- Simulate export jobs
- Validate outputs

### Phase 3 — Canary Traffic

- Route % traffic to new version
- Compare metrics
- Detect regressions

### Phase 4 — Observability

- Metrics collection (jobs, duration, failures)
- Logs correlation
- Alerts on anomalies

---

## Initial Tasks

- [ ] Add Playwright config
- [ ] Add first E2E test (export job flow)
- [ ] Add test dataset / fixtures
- [ ] Add synthetic traffic runner
- [ ] Add metrics assertions

---

## Next Steps

1. Implement Playwright setup
2. Add first real E2E test
3. Integrate into CI
4. Add canary logic

---

## Notes

- System already has strong DocBlock coverage
- API structure is stable
- Ready for production-grade validation layer
