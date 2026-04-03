import { test, expect } from '@playwright/test';
import { virtualUser } from '../support/cohorts';

test('virtual user synthetic canary flow', async ({ request }) => {
  const user = virtualUser();

  const response = await request.get('/api/analytics/export-jobs/metrics', {
    headers: {
      'X-Test-Cohort': user.cohort,
      'X-Test-User': user.id,
      'X-Canary': '1',
    },
  });

  expect(response.status()).toBe(200);

  const json = await response.json();

  expect(json).toHaveProperty('jobs_total');
  expect(typeof json.jobs_total).toBe('number');
});
