import { test, expect } from '@playwright/test';
import { existingUser } from '../support/cohorts';

test('existing cohort header does not prevent public status', async ({ request }) => {
  const user = existingUser();

  const response = await request.get('/status', {
    headers: {
      'X-Test-Cohort': user.cohort,
      'X-Test-User': user.id,
    },
  });

  expect(response.status()).toBe(200);

  expect(await response.text()).toContain('analytics');
});
