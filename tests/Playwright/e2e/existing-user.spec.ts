import { test, expect } from '@playwright/test';
import { existingUser } from '../support/cohorts';

test('existing user cohort can read analytics metrics', async ({ request }) => {
  const user = existingUser();

  const response = await request.get('/api/analytics/export-jobs/metrics', {
    headers: {
      'X-Test-Cohort': user.cohort,
      'X-Test-User': user.id,
    },
  });

  expect(response.status()).toBe(200);

  const json = await response.json();

  expect(json).toHaveProperty('jobs_total');
  expect(json).toHaveProperty('status_counts');
  expect(typeof json.jobs_total).toBe('number');
  expect(typeof json.status_counts.done).toBe('number');
});
