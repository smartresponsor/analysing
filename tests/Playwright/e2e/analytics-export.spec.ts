import { test, expect } from '@playwright/test';

test('analytics public status endpoint responds', async ({ request }) => {
  const response = await request.get('/status');

  expect(response.status()).toBe(200);

  expect(await response.text()).toContain('analytics');
});
