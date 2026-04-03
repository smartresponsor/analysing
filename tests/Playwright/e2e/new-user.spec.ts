import { test, expect } from '@playwright/test';
import { newUser } from '../support/cohorts';

test('new user cohort can access empty analytics safely', async ({ request }) => {
  const user = newUser();

  const response = await request.get('/api/analytics/export-jobs/metrics', {
    headers: {
      'X-Test-Cohort': user.cohort,
      'X-Test-User': user.id,
    },
  });

  expect(response.status()).toBe(200);

  const json = await response.json();

  expect(json).toHaveProperty('jobs_total');
  expect(json.jobs_total).toBeGreaterThanOrEqual(0);
});
