import { test, expect } from '@playwright/test';
import { virtualUser } from '../support/cohorts';

test('virtual user synthetic canary flow', async ({ request }) => {
  const user = virtualUser();

  const response = await request.get('/status', {
    headers: {
      'X-Test-Cohort': user.cohort,
      'X-Test-User': user.id,
      'X-Canary': '1',
    },
  });

  expect(response.status()).toBe(200);

  expect(await response.text()).toContain('analytics');
});
